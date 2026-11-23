<?php

use App\Models\User;

it('redirects guests to the admin login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('lets every staff role open the admin dashboard', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get('/admin')
        ->assertOk();
})->with(['admin', 'menuManager', 'orderOperator']);

it('does not offer public registration', function () {
    $this->get('/admin/register')->assertNotFound();
});
