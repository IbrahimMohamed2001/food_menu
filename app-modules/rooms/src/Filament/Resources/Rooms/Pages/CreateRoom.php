<?php

namespace Modules\Rooms\Filament\Resources\Rooms\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Rooms\Filament\Resources\Rooms\RoomResource;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;
}
