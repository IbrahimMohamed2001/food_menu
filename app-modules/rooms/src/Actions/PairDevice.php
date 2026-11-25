<?php

namespace Modules\Rooms\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Rooms\Exceptions\PairingCodeExpiredException;
use Modules\Rooms\Exceptions\PairingCodeNotFoundException;
use Modules\Rooms\Exceptions\PairingCodeUsedException;
use Modules\Rooms\Models\Device;
use Modules\Rooms\Models\DevicePairingCode;

/**
 * Pairs a tablet to a room using a one-time pairing code.
 *
 * Returns the new Device with its plain-text Sanctum token attached
 * as a transient property ($device->_plain_token).
 */
final class PairDevice
{
    /**
     * @param  string  $code  The raw code entered by the user (any case, with or without dash).
     * @param  string|null  $deviceName  Friendly name from the tablet.
     * @param  string|null  $deviceIdentifier  Installation UUID from the tablet.
     *
     * @throws PairingCodeNotFoundException
     * @throws PairingCodeExpiredException
     * @throws PairingCodeUsedException
     */
    public function execute(string $code, ?string $deviceName, ?string $deviceIdentifier): Device
    {
        $hash = hash('sha256', GeneratePairingCode::normalizeCode($code));

        $device = DB::transaction(function () use ($hash, $deviceName, $deviceIdentifier): Device {
            /** @var DevicePairingCode|null $pairingCode */
            $pairingCode = DevicePairingCode::where('code_hash', $hash)
                ->lockForUpdate()
                ->first();

            if ($pairingCode === null) {
                throw new PairingCodeNotFoundException;
            }

            if ($pairingCode->expires_at->isPast()) {
                throw new PairingCodeExpiredException;
            }

            if ($pairingCode->used_at !== null) {
                throw new PairingCodeUsedException;
            }

            $room = $pairingCode->room;

            // Revoke any existing device paired to this room.
            $room->activeDevice?->revoke();


            // Create a new device row paired to the room.
            $device = Device::create([
                'room_id' => $room->id,
                'name' => $deviceName,
                'identifier' => $deviceIdentifier,
                'paired_at' => now(),
            ]);

            // Mark the pairing code as used.
            $pairingCode->update([
                'used_at' => now(),
                'device_id' => $device->id,
            ]);

            return $device;
        });

        // Create the Sanctum token outside the transaction so a token failure
        // does not silently swallow the device creation.
        $device->plainTextToken = $device->createToken('device-token')->plainTextToken;

        return $device->load('room');
    }
}
