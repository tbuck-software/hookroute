<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\Delivery;
use App\Models\Destination;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Delivery> */
class DeliveryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'connection_id' => Connection::factory(),
            'destination_id' => Destination::factory(),
            'status' => 'pending',
            'attempts' => 0,
        ];
    }
}
