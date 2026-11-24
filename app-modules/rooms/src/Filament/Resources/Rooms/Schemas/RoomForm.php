<?php

namespace Modules\Rooms\Filament\Resources\Rooms\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('room_number')
                    ->label('Room number')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('floor')
                    ->label('Floor')
                    ->maxLength(20)
                    ->placeholder('e.g. G or 3'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->helperText('Inactive rooms cannot place orders.')
                    ->default(true),
            ]);
    }
}
