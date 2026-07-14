<?php

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration closes after the first account unless public registration is enabled', function () {
    User::factory()->create();

    $this->get('/register')->assertForbidden();
    $this->post('/register', [
        'name' => 'Unexpected User',
        'email' => 'unexpected@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertForbidden();
});

test('a pending project invitation permits registration for the invited email', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);
    $invitation = ProjectInvitation::create([
        'project_id' => $project->id,
        'invited_by' => $owner->id,
        'email' => 'invitee@example.test',
        'role' => 'member',
        'token' => str_repeat('b', 64),
        'expires_at' => now()->addDay(),
    ]);

    $this->get(route('invitations.show', $invitation->token))->assertRedirect(route('login'));
    $this->get('/register')->assertOk();
    $this->post('/register', [
        'name' => 'Invited User',
        'email' => 'invitee@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    $invitee = User::where('email', 'invitee@example.test')->firstOrFail();
    expect($project->members()->whereKey($invitee->id)->exists())->toBeTrue()
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();
});
