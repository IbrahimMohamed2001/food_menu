<?php

use App\Models\User;
use Modules\Rooms\Actions\GeneratePairingCode;
use Modules\Rooms\Models\Device;
use Modules\Rooms\Models\DevicePairingCode;
use Modules\Rooms\Models\Room;

function issueCode(Room $room): string
{
    $generator = new GeneratePairingCode;
    $generator->execute($room, User::factory()->admin()->create()->id);

    return $generator->plainCode();
}

it('pairs a tablet via POST /api/v1/devices/pair and returns token and device info', function () {
    $room = Room::factory()->create(['room_number' => '204', 'floor' => '2']);

    $this->postJson(route('api.v1.devices.pair'), [
        'code' => GeneratePairingCode::formatCode(issueCode($room)),
        'device_name' => 'Room 204 Tablet',
        'device_identifier' => 'device-uuid-204',
    ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['token', 'device' => ['name', 'paired_at', 'room' => ['number', 'floor']]]])
        ->assertJsonPath('data.device.name', 'Room 204 Tablet')
        ->assertJsonPath('data.device.room.number', '204')
        ->assertJsonPath('data.device.room.floor', '2');
});

it('accepts the code in lowercase and without the dash', function () {
    $room = Room::factory()->create();

    $this->postJson(route('api.v1.devices.pair'), ['code' => strtolower(issueCode($room))])
        ->assertCreated();
});

it('rejects an unknown pairing code', function () {
    $this->postJson(route('api.v1.devices.pair'), ['code' => 'WRONGCOD'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('rejects an expired pairing code', function () {
    DevicePairingCode::factory()->expired()->create(['code_hash' => hash('sha256', 'ABCD2345')]);

    $this->postJson(route('api.v1.devices.pair'), ['code' => 'ABCD-2345'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('rejects a pairing code that was already used', function () {
    $room = Room::factory()->create();
    $code = issueCode($room);

    $this->postJson(route('api.v1.devices.pair'), ['code' => $code])->assertCreated();
    $this->postJson(route('api.v1.devices.pair'), ['code' => $code])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('requires a code', function () {
    $this->postJson(route('api.v1.devices.pair'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('rate limits pairing attempts to 5 per minute per IP', function () {
    foreach (range(1, 5) as $i) {
        $this->postJson(route('api.v1.devices.pair'), ['code' => 'WRONGCOD'])->assertUnprocessable();
    }

    $this->postJson(route('api.v1.devices.pair'), ['code' => 'WRONGCOD'])->assertTooManyRequests();
});

it('revokes the old tablet token when a replacement is paired', function () {
    $room = Room::factory()->create();

    $oldToken = $this->postJson(route('api.v1.devices.pair'), ['code' => issueCode($room)])
        ->assertCreated()->json('data.token');

    $this->postJson(route('api.v1.devices.pair'), ['code' => issueCode($room)])->assertCreated();

    // Sanctum caches the resolved user per request cycle in tests.
    $this->app['auth']->forgetGuards();

    $this->withToken($oldToken)->getJson(route('api.v1.devices.me'))->assertUnauthorized();
});

it('returns device info for an authenticated device on GET /api/v1/devices/me', function () {
    $room = Room::factory()->create(['room_number' => '305', 'floor' => '3']);
    $device = Device::factory()->create(['room_id' => $room->id, 'name' => 'Room 305 Tablet']);
    $token = $device->createToken('test-token')->plainTextToken;

    $this->withToken($token)->getJson(route('api.v1.devices.me'))
        ->assertOk()
        ->assertJsonPath('data.device.name', 'Room 305 Tablet')
        ->assertJsonPath('data.device.room.number', '305')
        ->assertJsonPath('data.device.room.floor', '3');
});

it('updates last_seen_at on authenticated requests', function () {
    $device = Device::factory()->create();
    $token = $device->createToken('t')->plainTextToken;

    expect($device->last_seen_at)->toBeNull();

    $this->withToken($token)->getJson(route('api.v1.devices.me'))->assertOk();

    expect($device->fresh()->last_seen_at)->not->toBeNull();
});

it('returns 401 on /devices/me when unauthenticated', function () {
    $this->getJson(route('api.v1.devices.me'))->assertUnauthorized();
});

it('returns 401 for an unknown token', function () {
    $this->withToken('999|not-a-real-token')->getJson(route('api.v1.devices.me'))->assertUnauthorized();
});

it('returns 403 when the token belongs to a revoked device', function () {
    $device = Device::factory()->create();
    $device->revoke();
    $token = $device->createToken('late-token')->plainTextToken;

    $this->withToken($token)->getJson(route('api.v1.devices.me'))->assertForbidden();
});

it('does not let a staff session authenticate an API request', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->getJson(route('api.v1.devices.me'))->assertUnauthorized();
});
