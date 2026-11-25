<?php

use Illuminate\Support\Facades\Route;
use Modules\Rooms\Http\Controllers\Api\V1\DeviceController;
use Modules\Rooms\Http\Middleware\EnsureDeviceIsActive;

Route::middleware('api')
    ->prefix('api/v1')
    ->name('api.v1.')
    ->group(function () {
        // Public: exchange a one-time code for a device token (5 attempts/min per IP).
        Route::post('/devices/pair', [DeviceController::class, 'pair'])
            ->middleware('throttle:5,1')
            ->name('devices.pair');

        // Authenticated device routes (120 req/min per device).
        Route::middleware(['auth:sanctum', EnsureDeviceIsActive::class, 'throttle:120,1'])
            ->group(function () {
                Route::get('/devices/me', [DeviceController::class, 'me'])
                    ->name('devices.me');
            });
    });
