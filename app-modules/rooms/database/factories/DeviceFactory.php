<?php

namespace Modules\Rooms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Rooms\Models\Device;
use Modules\Rooms\Models\Room;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'name' => fake()->words(3, true).' tablet',
            'identifier' => fake()->uuid(),
            'paired_at' => now(),
            'last_seen_at' => null,
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state([
            'revoked_at' => now(),
            'room_id' => null,
        ]);
    }

    public function withoutRoom(): static
    {
        return $this->state(['room_id' => null]);
    }
}
