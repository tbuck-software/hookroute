<?php

use App\Models\Connection;
use App\Models\Delivery;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\Source;
use App\Models\User;
use App\Notifications\ProjectInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

it('lets members open a project and rejects outsiders', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);
    $project->members()->attach($member, ['role' => 'member']);

    $this->actingAs($member)->get(route('projects.dashboard', $project))->assertOk();
    $this->actingAs($outsider)->get(route('projects.dashboard', $project))->assertForbidden();
});

it('does not expose source secrets to read only members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);
    $project->members()->attach($member, ['role' => 'member']);
    Source::factory()->for($project)->create();

    $this->actingAs($member)
        ->get(route('projects.sources.index', $project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sources.0.webhook_url', null)
            ->where('currentProject.can_manage', false));
});

it('requires a signature header while an existing source hmac secret is retained', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);
    $source = Source::factory()->for($project)->create([
        'signing_secret' => 'inbound-signing-secret',
        'signature_header' => 'X-Hookroute-Signature',
    ]);

    $this->actingAs($owner)
        ->patch(route('projects.sources.update', [$project, $source]), [
            'name' => $source->name,
            'enabled' => true,
            'signing_secret' => '',
            'signature_header' => '',
            'clear_signing_secret' => false,
        ])
        ->assertSessionHasErrors('signature_header');

    $this->actingAs($owner)
        ->patch(route('projects.sources.update', [$project, $source]), [
            'name' => $source->name,
            'enabled' => true,
            'signing_secret' => '',
            'signature_header' => '',
            'clear_signing_secret' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($source->fresh()->signing_secret)->toBeNull()
        ->and($source->fresh()->signature_header)->toBeNull();
});

it('requires source hmac settings to be configured as a pair', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);

    $this->actingAs($owner)
        ->post(route('projects.sources.store', $project), [
            'name' => 'Incomplete source',
            'signing_secret' => '',
            'signature_header' => 'X-Signature',
        ])
        ->assertSessionHasErrors('signing_secret');

    $this->actingAs($owner)
        ->post(route('projects.sources.store', $project), [
            'name' => 'Incomplete source',
            'signing_secret' => 'inbound-signing-secret',
            'signature_header' => '',
        ])
        ->assertSessionHasErrors('signature_header');

    expect($project->sources()->count())->toBe(0);
});

it('does not expose destination credentials or recipient addresses to read only members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);
    $project->members()->attach($member, ['role' => 'member']);
    Destination::factory()->for($project)->email()->create([
        'config' => ['recipients' => ['private@example.test']],
    ]);

    $this->actingAs($member)
        ->get(route('projects.destinations.index', $project))
        ->assertInertia(fn (Assert $page) => $page
            ->where('destinations.0.config', null)
            ->where('destinations.0.summary', '1 recipient'));
});

it('allows an administrator to invite a user who can accept the invitation', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.test']);
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);

    $this->actingAs($owner)->post(route('projects.team.invite', $project), [
        'email' => $invitee->email,
        'role' => 'member',
    ])->assertRedirect();

    $invitation = ProjectInvitation::firstOrFail();
    Notification::assertSentTo($invitee, ProjectInvitationNotification::class);

    $this->actingAs($invitee)
        ->post(route('invitations.accept', $invitation->token))
        ->assertRedirect(route('projects.dashboard', $project));

    expect($project->members()->whereKey($invitee->id)->exists())->toBeTrue();
});

it('allows members to inspect events but only managers to replay deliveries', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);
    $project->members()->attach($member, ['role' => 'member']);
    $source = Source::factory()->for($project)->create();
    $destination = Destination::factory()->for($project)->create();
    $connection = Connection::factory()->for($project)->for($source)->for($destination)->create();
    $event = Event::factory()->for($project)->for($source)->create();
    $delivery = Delivery::factory()->for($event)->for($connection)->for($destination)->create();

    $this->actingAs($member)
        ->get(route('projects.events.show', [$project, $event]))
        ->assertOk();

    $this->actingAs($member)
        ->post(route('projects.deliveries.replay', [$project, $event, $delivery]))
        ->assertForbidden();
});

it('lets only the current owner transfer project ownership to a member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);
    $project->members()->attach($member, ['role' => 'member']);

    $this->actingAs($member)
        ->post(route('projects.team.transfer-owner', [$project, $owner]))
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('projects.team.transfer-owner', [$project, $member]))
        ->assertRedirect();

    expect($project->fresh()->owner_id)->toBe($member->id)
        ->and($project->roleFor($member)?->value)->toBe('owner')
        ->and($project->roleFor($owner)?->value)->toBe('admin');
});

it('leaves exactly one owner role and never trusts a stale owner pivot', function () {
    $owner = User::factory()->create();
    $staleOwner = User::factory()->create();
    $newOwner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);
    $project->members()->attach($staleOwner, ['role' => 'owner']);
    $project->members()->attach($newOwner, ['role' => 'member']);

    $this->actingAs($staleOwner)
        ->delete(route('projects.destroy', $project))
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('projects.team.transfer-owner', [$project, $newOwner]))
        ->assertRedirect();

    expect($project->members()->wherePivot('role', 'owner')->count())->toBe(1)
        ->and($project->roleFor($newOwner)?->value)->toBe('owner')
        ->and($project->roleFor($staleOwner)?->value)->toBe('admin');
});

it('does not expose pending invitation tokens and hides invitations from members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);
    $project->members()->attach($member, ['role' => 'member']);
    ProjectInvitation::create([
        'project_id' => $project->id,
        'invited_by' => $owner->id,
        'email' => 'new@example.test',
        'role' => 'member',
        'token' => str_repeat('a', 64),
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($owner)
        ->get(route('projects.team.index', $project))
        ->assertInertia(fn (Assert $page) => $page
            ->has('invitations', 1)
            ->missing('invitations.0.token'));

    $this->actingAs($member)
        ->get(route('projects.team.index', $project))
        ->assertInertia(fn (Assert $page) => $page->has('invitations', 0));
});

it('hides destination response bodies from read only members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);
    $project->members()->attach($member, ['role' => 'member']);
    $source = Source::factory()->for($project)->create();
    $destination = Destination::factory()->for($project)->create();
    $connection = Connection::factory()->for($project)->for($source)->for($destination)->create();
    $event = Event::factory()->for($project)->for($source)->create();
    Delivery::factory()->for($event)->for($connection)->for($destination)->create([
        'response_excerpt' => 'private downstream response',
    ]);

    $this->actingAs($member)
        ->get(route('projects.events.show', [$project, $event]))
        ->assertInertia(fn (Assert $page) => $page->missing('event.deliveries.0.response_excerpt'));
});
