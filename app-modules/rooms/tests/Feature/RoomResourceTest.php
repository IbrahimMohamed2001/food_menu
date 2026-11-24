<?php

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Modules\Rooms\Filament\Resources\Rooms\Actions\GeneratePairingCodeAction;
use Modules\Rooms\Filament\Resources\Rooms\Pages\CreateRoom;
use Modules\Rooms\Filament\Resources\Rooms\Pages\EditRoom;
use Modules\Rooms\Filament\Resources\Rooms\Pages\ListRooms;
use Modules\Rooms\Filament\Resources\Rooms\RoomResource;
use Modules\Rooms\Models\Device;
use Modules\Rooms\Models\Room;

it('returns 403 to non-administrators opening room management', function () {
    $this->actingAs(User::factory()->menuManager()->create())
        ->get(RoomResource::getUrl('index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->orderOperator()->create())
        ->get(RoomResource::getUrl('index'))
        ->assertForbidden();
});

it('lists rooms for administrators', function () {
    $this->actingAs(User::factory()->admin()->create());
    $rooms = Room::factory()->count(3)->create();

    Livewire::test(ListRooms::class)
        ->assertOk()
        ->assertCanSeeTableRecords($rooms);
});

it('creates a room', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateRoom::class)
        ->fillForm([
            'room_number' => '101',
            'floor' => '1',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $room = Room::where('room_number', '101')->sole();
    expect($room->floor)->toBe('1')
        ->and($room->is_active)->toBeTrue();
});

it('rejects duplicate room number', function () {
    $this->actingAs(User::factory()->admin()->create());
    Room::factory()->create(['room_number' => '102']);

    Livewire::test(CreateRoom::class)
        ->fillForm([
            'room_number' => '102',
            'floor' => '1',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['room_number' => 'unique']);
});

it('generates pairing code via header action on EditRoom page', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $room = Room::factory()->create(['room_number' => '105']);

    Livewire::test(EditRoom::class, ['record' => $room->getRouteKey()])
        ->callAction(GeneratePairingCodeAction::class)
        ->assertHasNoErrors()
        ->assertNotified();

    expect($room->pairingCodes()->count())->toBe(1)
        ->and($room->pairingCodes()->first()->isValid())->toBeTrue();
});

it('does not allow deleting a room with an active paired device', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $room = Room::factory()->create();
    Device::factory()->create(['room_id' => $room->id]);

    Livewire::test(EditRoom::class, ['record' => $room->getRouteKey()])
        ->assertActionDisabled('delete');
});
