<?php

namespace Modules\Rooms;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Rooms\Filament\Resources\Rooms\RoomResource;

class RoomsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'rooms';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__ . '/Filament/Resources',
            for: 'Modules\\Rooms\\Filament\\Resources',
        );
    }

    public function boot(Panel $panel): void {}
}
