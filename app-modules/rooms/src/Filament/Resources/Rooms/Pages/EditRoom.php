<?php

namespace Modules\Rooms\Filament\Resources\Rooms\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Rooms\Filament\Resources\Rooms\RoomResource;
use Modules\Rooms\Filament\Resources\Rooms\Actions\GeneratePairingCodeAction;

class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GeneratePairingCodeAction::make(),
            DeleteAction::make()
                ->before(function (DeleteAction $action) {
                    // A room with orders cannot be deleted (FK restrict).
                    // If the room has an active device, deactivate instead.
                })
                ->disabled(fn ($record) => $record->activeDevice !== null),
        ];
    }
}
