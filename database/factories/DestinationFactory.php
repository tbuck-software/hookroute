<?php

namespace Database\Factories;

use App\Models\Destination;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Destination> */
class DestinationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(2, true),
            'type' => 'webhook',
            'config' => [
                'url' => 'https://receiver.example/webhooks',
                'method' => 'POST',
                'headers' => [],
                'signing_secret' => null,
            ],
            'enabled' => true,
        ];
    }

    public function webhook(): static
    {
        return $this->state(fn () => [
            'type' => 'webhook',
            'config' => ['url' => 'https://receiver.example/webhooks', 'method' => 'POST', 'headers' => []],
        ]);
    }

    public function discord(): static
    {
        return $this->state(fn () => [
            'type' => 'discord',
            'config' => ['url' => 'https://discord.com/api/webhooks/123/token', 'username' => 'Hookroute'],
        ]);
    }

    public function email(): static
    {
        return $this->state(fn () => [
            'type' => 'email',
            'config' => ['recipients' => ['team@example.test']],
        ]);
    }

    public function digest(): static
    {
        return $this->state(fn () => [
            'type' => 'digest',
            'config' => [
                'recipients' => ['team@example.test'],
                'send_time' => '18:00',
                'window_start_time' => '08:00',
                'timezone' => 'Europe/Berlin',
                'subject' => 'Event digest',
                'send_empty' => false,
            ],
        ]);
    }
}
