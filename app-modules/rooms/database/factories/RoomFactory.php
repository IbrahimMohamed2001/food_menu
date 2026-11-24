<?php

namespace Modules\Rooms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Rooms\Models\Room;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'room_number' => (string) fake()->unique()->numberBetween(100, 999),
            'floor' => (string) fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
