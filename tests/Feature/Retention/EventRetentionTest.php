<?php

use App\Models\Event;
use App\Models\Project;
use App\Models\Source;

it('prunes events according to each project retention setting', function () {
    $project = Project::factory()->create(['event_retention_days' => 7]);
    $source = Source::factory()->for($project)->create();
    Event::factory()->for($project)->for($source)->create(['received_at' => now()->subDays(8)]);
    $recent = Event::factory()->for($project)->for($source)->create(['received_at' => now()->subDays(6)]);

    $this->artisan('events:prune')->assertSuccessful();

    expect(Event::count())->toBe(1)->and($recent->fresh())->not->toBeNull();
});

it('prunes more events than a single delete batch holds', function () {
    config(['hookroute.prune_batch_size' => 2]);
    $project = Project::factory()->create(['event_retention_days' => 7]);
    $source = Source::factory()->for($project)->create();
    Event::factory()->count(5)->for($project)->for($source)->create(['received_at' => now()->subDays(8)]);

    $this->artisan('events:prune')->assertSuccessful();

    expect(Event::count())->toBe(0);
});
