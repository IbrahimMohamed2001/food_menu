<?php

namespace Modules\Rooms\Filament\Resources\Rooms\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Rooms\Filament\Resources\Rooms\RoomResource;
use Modules\Rooms\Filament\Resources\Rooms\Actions\GeneratePairingCodeAction;
use Modules\Rooms\Filament\Resources\Rooms\Actions\UnpairTabletAction;
use Modules\Rooms\Models\Room;

class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GeneratePairingCodeAction::make(),
            UnpairTabletAction::make(),
            DeleteAction::make()
                ->disabled(fn(Room $record): bool => $record->activeDevice !== null),
        ];
    }
}
