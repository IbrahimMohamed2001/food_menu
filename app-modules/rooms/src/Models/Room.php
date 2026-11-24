<?php

namespace Modules\Rooms\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Rooms\Database\Factories\RoomFactory;

#[Fillable(['room_number', 'floor', 'is_active'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * The active, non-revoked device currently paired to this room.
     */
    public function activeDevice(): HasOne
    {
        return $this->hasOne(Device::class)->whereNull('revoked_at');
    }

    /**
     * All devices (including revoked) ever paired to this room.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Pairing codes issued for this room.
     */
    public function pairingCodes(): HasMany
    {
        return $this->hasMany(DevicePairingCode::class);
    }
}
