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
