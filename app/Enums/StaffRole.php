<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StaffRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case MenuManager = 'menu_manager';
    case OrderOperator = 'order_operator';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::MenuManager => 'Menu manager',
            self::OrderOperator => 'Order operator',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::MenuManager => 'info',
            self::OrderOperator => 'success',
        };
    }
}
