<?php

namespace Modules\Rooms\Filament\Resources\Rooms\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Modules\Rooms\Actions\RevokeDevice;
use Modules\Rooms\Models\Room;

class UnpairTabletAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'unpair_tablet';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Unpair tablet')
            ->icon('heroicon-o-link-slash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('The tablet loses access immediately and must be paired again with a new code.')
            ->visible(fn(Room $record): bool => $record->activeDevice !== null)
            ->action(function (Room $record, RevokeDevice $revokeDevice): void {
                $device = $record->activeDevice;

                if ($device === null) {
                    return;
                }

                $revokeDevice->execute($device);
                $record->unsetRelation('activeDevice'); // refresh the disabled state of Delete

                Notification::make()->title('Tablet unpaired')->success()->send();
            });
    }
}
