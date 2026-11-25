<?php

namespace Modules\Rooms\Providers;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Rooms\Models\Room;
use Modules\Rooms\Policies\RoomPolicy;
use Modules\Rooms\RoomsPlugin;

class RoomsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Gate::policy(Room::class, RoomPolicy::class);

        Panel::configureUsing(function (Panel $panel) {
            if ($panel->getId() === 'admin') {
                $panel->plugin(RoomsPlugin::make());
            }
        });
    }
}
