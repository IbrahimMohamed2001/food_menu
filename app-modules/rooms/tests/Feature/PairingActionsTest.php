<?php

use App\Models\User;
use Modules\Rooms\Actions\GeneratePairingCode;
use Modules\Rooms\Actions\PairDevice;
use Modules\Rooms\Exceptions\PairingCodeExpiredException;
use Modules\Rooms\Exceptions\PairingCodeNotFoundException;
use Modules\Rooms\Exceptions\PairingCodeUsedException;
use Modules\Rooms\Models\DevicePairingCode;
use Modules\Rooms\Models\Room;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('generates a valid pairing code for a room and invalidates previous unused codes', function () {
    $room = Room::factory()->create();
    $action = new GeneratePairingCode;

    $firstCode = $action->execute($room, $this->admin->id);
    $firstPlain = $action->plainCode();

    expect(strlen($firstPlain))->toBe(8)
        ->and($firstPlain)->toMatch('/^[23456789A-HJ-NP-Z]{8}$/')
        ->and($firstCode->room_id)->toBe($room->id)
        ->and($firstCode->created_by_user_id)->toBe($this->admin->id)
        ->and($firstCode->used_at)->toBeNull()
        ->and($firstCode->expires_at->isFuture())->toBeTrue()
        ->and(hash('sha256', $firstPlain))->toBe($firstCode->code_hash);

    $secondCode = $action->execute($room, $this->admin->id);

    expect(DevicePairingCode::find($firstCode->id))->toBeNull()
        ->and(DevicePairingCode::find($secondCode->id))->not->toBeNull();
});

it('does not invalidate codes issued for other rooms', function () {
    $roomA = Room::factory()->create();
    $roomB = Room::factory()->create();
    $action = new GeneratePairingCode;

    $codeA = $action->execute($roomA, $this->admin->id);
    $action->execute($roomB, $this->admin->id);

    expect(DevicePairingCode::find($codeA->id))->not->toBeNull();
});

it('formats and normalizes codes', function () {
    expect(GeneratePairingCode::formatCode('k7q2xm4p'))->toBe('K7Q2-XM4P')
        ->and(GeneratePairingCode::normalizeCode('k7q2-xm4p'))->toBe('K7Q2XM4P')
        ->and(GeneratePairingCode::normalizeCode(' k7q2 xm4p '))->toBe('K7Q2XM4P');
});

it('pairs a device with a valid pairing code and revokes the previous active device', function () {
    $room = Room::factory()->create();
    $generate = new GeneratePairingCode;
    $pair = new PairDevice;

    $codeRecord = $generate->execute($room, $this->admin->id);
    $plainCode = $generate->plainCode();

    $device1 = $pair->execute(
        code: strtolower(GeneratePairingCode::formatCode($plainCode)),
        deviceName: 'Tablet 101',
        deviceIdentifier: 'uuid-101',
    );

    expect($device1->room_id)->toBe($room->id)
        ->and($device1->isActive())->toBeTrue()
        ->and($device1->name)->toBe('Tablet 101')
        ->and($device1->tokens()->count())->toBe(1)
        ->and($codeRecord->fresh()->used_at)->not->toBeNull()
        ->and($codeRecord->fresh()->device_id)->toBe($device1->id);

    $generate->execute($room, $this->admin->id);

    $device2 = $pair->execute($generate->plainCode(), 'Tablet 101 Replacement', 'uuid-102');

    $device1->refresh();

    expect($device1->isActive())->toBeFalse()
        ->and($device1->revoked_at)->not->toBeNull()
        ->and($device1->room_id)->toBeNull()
        ->and($device1->tokens()->count())->toBe(0)
        ->and($device2->room_id)->toBe($room->id)
        ->and($device2->isActive())->toBeTrue();
});

it('throws when the pairing code is not found', function () {
    (new PairDevice)->execute('INVALIDCODE', 'Tablet', 'uuid');
})->throws(PairingCodeNotFoundException::class);

it('throws when the pairing code is expired', function () {
    $room = Room::factory()->create();
    DevicePairingCode::factory()->expired()->create([
        'room_id' => $room->id,
        'code_hash' => hash('sha256', 'ABCD2345'),
    ]);

    (new PairDevice)->execute('abcd-2345', 'Tablet', 'uuid');
})->throws(PairingCodeExpiredException::class);

it('throws when the pairing code has already been used', function () {
    $room = Room::factory()->create();
    $generate = new GeneratePairingCode;
    $generate->execute($room, $this->admin->id);
    $plainCode = $generate->plainCode();

    $pair = new PairDevice;
    $pair->execute($plainCode, 'Tablet 1', 'uuid-1');
    $pair->execute($plainCode, 'Tablet 2', 'uuid-2');
})->throws(PairingCodeUsedException::class);
