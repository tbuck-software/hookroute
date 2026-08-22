<?php

use App\Models\Event;
use App\Models\Project;
use App\Models\Source;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('filters events by source public identifier without exposing internal ids', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();
    $project->members()->attach($owner, ['role' => 'owner']);
    $alpha = Source::factory()->for($project)->create(['name' => 'Alpha']);
    $beta = Source::factory()->for($project)->create(['name' => 'Beta']);
    Event::factory()->for($alpha)->for($project)->create();
    Event::factory()->for($beta)->for($project)->create();

    $this->actingAs($owner)
        ->get(route('projects.events.index', [$project, 'source' => $beta->public_id]))
        ->assertOk()
        ->assertInertia(function (Assert $page) use ($alpha, $beta) {
            $page->has('events.data', 1)
                ->where('events.data.0.source.id', $beta->public_id)
                // Sources are identified by public_id, not the internal key.
                ->where('sources', fn ($sources) => collect($sources)->pluck('public_id')->sort()->values()->all()
                    === collect([$alpha->public_id, $beta->public_id])->sort()->values()->all());
        });

    $this->actingAs($owner)
        ->get(route('projects.events.index', [$project, 'source' => 'not-a-real-id']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('events.data', 0));
});
