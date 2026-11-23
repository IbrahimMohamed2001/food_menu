<?php

use App\Models\User;

test('only administrators manage staff accounts', function (string $role, bool $isAllowed) {
    $user = User::factory()->{$role}()->create();
    $otherStaffMember = User::factory()->create();

    expect($user->can('viewAny', User::class))->toBe($isAllowed)
        ->and($user->can('view', $otherStaffMember))->toBe($isAllowed)
        ->and($user->can('create', User::class))->toBe($isAllowed)
        ->and($user->can('update', $otherStaffMember))->toBe($isAllowed)
        ->and($user->can('delete', $otherStaffMember))->toBe($isAllowed);
})->with([
    'administrator' => ['admin', true],
    'menu manager' => ['menuManager', false],
    'order operator' => ['orderOperator', false],
]);

test('administrators cannot delete their own account', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('delete', $admin))->toBeFalse();
});
