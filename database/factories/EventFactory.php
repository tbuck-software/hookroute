<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Project;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $payload = ['type' => 'example.created', 'value' => fake()->numberBetween(1, 100)];

        return [
            'project_id' => Project::factory(),
            'source_id' => Source::factory(),
            'public_id' => (string) Str::ulid(),
            'method' => 'POST',
            'content_type' => 'application/json',
            'headers' => ['content-type' => ['application/json']],
            'raw_body' => json_encode($payload),
            'payload' => $payload,
            'idempotency_key' => null,
            'received_at' => now(),
        ];
    }
}
