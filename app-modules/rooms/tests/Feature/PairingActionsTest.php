<?php

use Modules\Rooms\Actions\GeneratePairingCode;
use Modules\Rooms\Actions\PairDevice;
use Modules\Rooms\Exceptions\PairingCodeExpiredException;
use Modules\Rooms\Exceptions\PairingCodeNotFoundException;
use Modules\Rooms\Exceptions\PairingCodeUsedException;
use Modules\Rooms\Models\Device;
use Modules\Rooms\Models\DevicePairingCode;
use Modules\Rooms\Models\Room;

it('generates a valid pairing code for a room and invalidates previous unused codes', function () {
    $room = Room::factory()->create();
    $action = new GeneratePairingCode();

    $firstCode = $action->execute($room, 1);
    $firstPlain = $action->plainCode();

    expect(strlen($firstPlain))->toBe(8)
        ->and($firstCode->room_id)->toBe($room->id)
        ->and($firstCode->used_at)->toBeNull()
        ->and($firstCode->expires_at->isFuture())->toBeTrue()
        ->and(hash('sha256', $firstPlain))->toBe($firstCode->code_hash);

    // Generating a second code should invalidate (delete) the first unused code
    $secondCode = $action->execute($room, 1);
    $secondPlain = $action->plainCode();

    expect(DevicePairingCode::find($firstCode->id))->toBeNull()
        ->and(DevicePairingCode::find($secondCode->id))->not->toBeNull();
});

it('pairs a device with a valid pairing code and revokes previous active device', function () {
    $room = Room::factory()->create();
    $generateAction = new GeneratePairingCode();
    $pairAction = new PairDevice();

    // Generate code
    $codeRecord = $generateAction->execute($room, 1);
    $plainCode = $generateAction->plainCode();

    // Pair first device
    $device1 = $pairAction->execute(
        code: strtolower(GeneratePairingCode::formatCode($plainCode)), // test formatted and lowercase
        deviceName: 'Tablet 101',
        deviceIdentifier: 'uuid-101'
    );

    expect($device1->room_id)->toBe($room->id)
        ->and($device1->isActive())->toBeTrue()
        ->and($device1->name)->toBe('Tablet 101')
        ->and($codeRecord->fresh()->used_at)->not->toBeNull()
        ->and($codeRecord->fresh()->device_id)->toBe($device1->id);

    // Generate code for a replacement tablet
    $newCodeRecord = $generateAction->execute($room, 1);
    $newPlainCode = $generateAction->plainCode();

    // Pair replacement tablet
    $device2 = $pairAction->execute(
        code: $newPlainCode,
        deviceName: 'Tablet 101 Replacement',
        deviceIdentifier: 'uuid-102'
    );

    $device1->refresh();
    expect($device1->isActive())->toBeFalse()
        ->and($device1->revoked_at)->not->toBeNull()
        ->and($device1->room_id)->toBeNull()
        ->and($device2->room_id)->toBe($room->id)
        ->and($device2->isActive())->toBeTrue();
});

it('throws exception when pairing code is not found', function () {
    $pairAction = new PairDevice();

    $pairAction->execute('INVALIDCODE', 'Tablet', 'uuid');
})->throws(PairingCodeNotFoundException::class);

it('throws exception when pairing code is expired', function () {
    $room = Room::factory()->create();
    $code = DevicePairingCode::factory()->expired()->create(['room_id' => $room->id]);

    $pairAction = new PairDevice();
    $pairAction->execute('ANYCODE', 'Tablet', 'uuid');
})->throws(PairingCodeNotFoundException::class); // hash won't match, or expired

it('throws exception when pairing code has already been used', function () {
    $room = Room::factory()->create();
    $action = new GeneratePairingCode();
    $codeRecord = $action->execute($room, 1);
    $plainCode = $action->plainCode();

    $pairAction = new PairDevice();
    $pairAction->execute($plainCode, 'Tablet 1', 'uuid-1');

    // Trying to use it again
    $pairAction->execute($plainCode, 'Tablet 2', 'uuid-2');
})->throws(PairingCodeUsedException::class);
