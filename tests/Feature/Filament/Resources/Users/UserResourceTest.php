<?php

use App\Enums\StaffRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('returns 403 to non-administrators opening staff management', function () {
    $this->actingAs(User::factory()->menuManager()->create())
        ->get(UserResource::getUrl('index'))
        ->assertForbidden();
});

it('lists staff members for administrators', function () {
    $this->actingAs(User::factory()->admin()->create());
    $staffMembers = User::factory()->count(3)->create();

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($staffMembers);
});

it('creates a staff member with a role and a hashed password', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Mona Hassan',
            'email' => 'mona@example.com',
            'role' => StaffRole::OrderOperator,
            'password' => 'kitchen-secret',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $staffMember = User::query()->where('email', 'mona@example.com')->sole();

    expect($staffMember->role)->toBe(StaffRole::OrderOperator)
        ->and(Hash::check('kitchen-secret', $staffMember->password))->toBeTrue();
});

it('rejects an email address that another staff member already uses', function () {
    $this->actingAs(User::factory()->admin()->create());
    $existingStaffMember = User::factory()->create();

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Duplicate',
            'email' => $existingStaffMember->email,
            'role' => StaffRole::MenuManager,
            'password' => 'another-secret',
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);
});

it('keeps the current password when the password field is left blank', function () {
    $this->actingAs(User::factory()->admin()->create());
    $staffMember = User::factory()->create(['password' => 'original-secret']);

    Livewire::test(EditUser::class, ['record' => $staffMember->getRouteKey()])
        ->fillForm(['name' => 'Renamed Member', 'password' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    $staffMember->refresh();

    expect($staffMember->name)->toBe('Renamed Member')
        ->and(Hash::check('original-secret', $staffMember->password))->toBeTrue();
});

it('does not let administrators delete or demote themselves', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->assertActionHidden('delete')
        ->assertFormFieldDisabled('role');
});

it('deletes another staff member', function () {
    $this->actingAs(User::factory()->admin()->create());
    $staffMember = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('delete')->table($staffMember));

    $this->assertModelMissing($staffMember);
});
