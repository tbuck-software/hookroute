<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Source> */
class SourceFactory extends Factory
{
    public function definition(): array
    {
        $secret = 'source-secret-'.Str::random(24);

        return [
            'project_id' => Project::factory(),
            'public_id' => (string) Str::ulid(),
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'secret' => $secret,
            'secret_hash' => hash('sha256', $secret),
            'signing_secret' => null,
            'signature_header' => null,
            'enabled' => true,
        ];
    }
}
