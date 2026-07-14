<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\Destination;
use App\Models\DigestRun;
use App\Models\Event;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DigestRun> */
class DigestRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'destination_id' => Destination::factory()->digest(),
            'window_start' => now()->startOfDay()->addHours(8),
            'window_end' => now()->startOfDay()->addHours(18),
            'event_ids' => [],
            'event_count' => 0,
            'total_event_count' => 0,
            'truncated' => false,
            'status' => 'pending',
        ];
    }

    public function withEvents(int $count): static
    {
        return $this->afterCreating(function (DigestRun $run) use ($count) {
            $destination = $run->destination;
            $source = Source::factory()->for($destination->project)->create();
            Connection::factory()->for($destination->project)->for($source)->for($destination)->create();
            $events = Event::factory()->count($count)->for($destination->project)->for($source)->create();
            $run->update([
                'event_ids' => $events->modelKeys(),
                'event_count' => $events->count(),
                'total_event_count' => $events->count(),
            ]);
        });
    }
}
