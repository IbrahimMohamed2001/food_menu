<?php

namespace Modules\Rooms\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Rooms\Models\DevicePairingCode;
use Modules\Rooms\Models\Room;

/**
 * @extends Factory<DevicePairingCode>
 */
class DevicePairingCodeFactory extends Factory
{
    protected $model = DevicePairingCode::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'code_hash' => hash('sha256', Str::upper(fake()->bothify('########'))),
            'expires_at' => now()->addMinutes(15),
            'used_at' => null,
            'device_id' => null,
            'created_by_user_id' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinutes(1)]);
    }

    public function used(): static
    {
        return $this->state(['used_at' => now()->subMinutes(5)]);
    }
}
