<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\Destination;
use App\Models\Project;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Connection> */
class ConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'source_id' => Source::factory(),
            'destination_id' => Destination::factory(),
            'name' => fake()->words(3, true),
            'filters' => [],
            'payload_mode' => 'passthrough',
            'subject_template' => null,
            'body_template' => null,
            'enabled' => true,
        ];
    }
}
