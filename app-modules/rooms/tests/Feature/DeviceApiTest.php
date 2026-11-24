<?php

use Modules\Rooms\Actions\GeneratePairingCode;
use Modules\Rooms\Models\Device;
use Modules\Rooms\Models\Room;

it('allows pairing tablet via POST /api/v1/devices/pair and returns token and device info', function () {
    $room = Room::factory()->create(['room_number' => '204', 'floor' => '2']);
    $generator = new GeneratePairingCode();
    $generator->execute($room, 1);
    $plainCode = $generator->plainCode();

    $response = $this->postJson(route('api.v1.devices.pair'), [
        'code' => GeneratePairingCode::formatCode($plainCode),
        'device_name' => 'Room 204 Tablet',
        'device_identifier' => 'device-uuid-204',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'token',
                'device' => [
                    'name',
                    'paired_at',
                    'room' => ['number', 'floor'],
                ],
            ],
        ])
        ->assertJsonPath('data.device.name', 'Room 204 Tablet')
        ->assertJsonPath('data.device.room.number', '204')
        ->assertJsonPath('data.device.room.floor', '2');
});

it('rejects pairing with invalid or reused code', function () {
    $response = $this->postJson(route('api.v1.devices.pair'), [
        'code' => 'WRONGCOD',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('returns device info for authenticated device on GET /api/v1/devices/me', function () {
    $room = Room::factory()->create(['room_number' => '305', 'floor' => '3']);
    $device = Device::factory()->create(['room_id' => $room->id, 'name' => 'Room 305 Tablet']);
    $token = $device->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(route('api.v1.devices.me'));

    $response->assertOk()
        ->assertJsonPath('data.device.name', 'Room 305 Tablet')
        ->assertJsonPath('data.device.room.number', '305')
        ->assertJsonPath('data.device.room.floor', '3');
});

it('returns 401 on /api/v1/devices/me when unauthenticated', function () {
    $response = $this->getJson(route('api.v1.devices.me'));

    $response->assertUnauthorized();
});

it('returns 403 on /api/v1/devices/me when device has been revoked', function () {
    $room = Room::factory()->create();
    $device = Device::factory()->create(['room_id' => $room->id]);
    $token = $device->createToken('test-token')->plainTextToken;

    // Revoke device
    $device->revoke();

    // Since revoke deletes tokens, let's create a token on a revoked device to test EnsureDeviceIsActive
    $token2 = $device->createToken('test-token-2')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token2}")
        ->getJson(route('api.v1.devices.me'));

    $response->assertForbidden();
});
