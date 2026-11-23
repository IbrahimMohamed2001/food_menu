<?php

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates an administrator account', function () {
    $this->artisan('app:create-admin', ['email' => 'owner@example.com', '--name' => 'Hotel Owner'])
        ->expectsQuestion('Password', 'owner-secret')
        ->assertSuccessful();

    $admin = User::query()->where('email', 'owner@example.com')->sole();

    expect($admin->name)->toBe('Hotel Owner')
        ->and($admin->role)->toBe(StaffRole::Admin)
        ->and(Hash::check('owner-secret', $admin->password))->toBeTrue();
});

it('promotes an existing staff account to administrator', function () {
    $staffMember = User::factory()->orderOperator()->create();

    $this->artisan('app:create-admin', ['email' => $staffMember->email])
        ->assertSuccessful();

    expect($staffMember->refresh()->role)->toBe(StaffRole::Admin)
        ->and(User::query()->count())->toBe(1);
});
