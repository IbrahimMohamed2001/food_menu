<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\StaffRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => StaffRole::class,
        ];
    }

    /**
     * Every staff account with a role may sign in to the hotel admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->role instanceof StaffRole;
    }

    /**
     * Determine whether the user administers the whole installation.
     */
    public function isAdmin(): bool
    {
        return $this->role === StaffRole::Admin;
    }

    /**
     * Determine whether the user may manage menu categories and items.
     */
    public function canManageMenu(): bool
    {
        return in_array($this->role, [StaffRole::Admin, StaffRole::MenuManager], true);
    }

    /**
     * Determine whether the user may view and process guest orders.
     */
    public function canProcessOrders(): bool
    {
        return in_array($this->role, [StaffRole::Admin, StaffRole::OrderOperator], true);
    }
}
