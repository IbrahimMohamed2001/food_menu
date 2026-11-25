<?php

namespace Modules\Rooms\Filament\Resources\Rooms;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Rooms\Filament\Resources\Rooms\Pages\CreateRoom;
use Modules\Rooms\Filament\Resources\Rooms\Pages\EditRoom;
use Modules\Rooms\Filament\Resources\Rooms\Pages\ListRooms;
use Modules\Rooms\Filament\Resources\Rooms\Schemas\RoomForm;
use Modules\Rooms\Filament\Resources\Rooms\Tables\RoomsTable;
use Modules\Rooms\Models\Room;
use UnitEnum;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $slug = 'rooms';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Hotel';

    protected static ?string $navigationLabel = 'Rooms';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'room';

    protected static ?string $pluralModelLabel = 'rooms';

    protected static ?string $recordTitleAttribute = 'room_number';

    public static function form(Schema $schema): Schema
    {
        return RoomForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRooms::route('/'),
            'create' => CreateRoom::route('/create'),
            'edit' => EditRoom::route('/{record}/edit'),
        ];
    }
}
