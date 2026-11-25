<?php

namespace Modules\Rooms\Filament\Resources\Rooms\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Modules\Rooms\Actions\GeneratePairingCode;
use Modules\Rooms\Models\Room;

/**
 * Filament page action that generates a pairing code and shows it to the staff member.
 */
class GeneratePairingCodeAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generate_pairing_code';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Pair tablet')
            ->icon('heroicon-o-device-tablet')
            ->color('primary')
            ->requiresConfirmation(false)
            ->action(function (Room $record, GeneratePairingCode $generatePairingCode): void {
                $pairingCode = $generatePairingCode->execute(
                    room: $record,
                    createdByUserId: Auth::id(),
                );

                $plainCode = $generatePairingCode->plainCode();
                $formatted = GeneratePairingCode::formatCode($plainCode);

                Notification::make()
                    ->title('Pairing code generated')
                    ->body(str("Enter **{$formatted}** on the tablet. The code expires in 15 minutes.")->markdown()->toHtmlString())
                    ->success()
                    ->persistent()
                    ->send();
            });
    }
}
