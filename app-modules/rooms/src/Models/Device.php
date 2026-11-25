<?php

namespace Modules\Rooms\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Rooms\Database\Factories\DeviceFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Authenticatable;


#[Fillable(['room_id', 'name', 'identifier', 'paired_at', 'last_seen_at', 'revoked_at'])]
class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use Authenticatable, HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paired_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * The plain-text Sanctum token, available only on the instance returned by PairDevice.
     * It is a declared property, so Eloquent never treats it as an attribute or relation
     * and it is never persisted.
     */
    public ?string $plainTextToken = null;


    /**
     * The room this device is paired to (null when revoked).
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * The pairing codes that created this device.
     */
    public function pairingCodes(): HasMany
    {
        return $this->hasMany(DevicePairingCode::class);
    }

    /**
     * Determine whether this device is currently active (paired, not revoked).
     */
    public function isActive(): bool
    {
        return $this->revoked_at === null && $this->room_id !== null;
    }

    /**
     * Revoke this device: set revoked_at, clear room_id, and delete its tokens.
     */
    public function revoke(): void
    {
        DB::transaction(function () {
            $this->tokens()->delete();
            $this->update([
                'revoked_at' => now(),
                'room_id' => null
            ]);
        });
    }

    /**
     * Update last_seen_at at most once per minute to avoid excessive writes.
     */
    public function touchLastSeen(): void
    {
        if ($this->last_seen_at === null || $this->last_seen_at->diffInSeconds(now()) >= 60) {
            $this->update(['last_seen_at' => now()]);
        }
    }
}
