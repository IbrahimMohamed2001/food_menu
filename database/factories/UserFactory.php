<?php

namespace Database\Factories;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => StaffRole::OrderOperator,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user administers the installation.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => StaffRole::Admin,
        ]);
    }

    /**
     * Indicate that the user manages the menu.
     */
    public function menuManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => StaffRole::MenuManager,
        ]);
    }

    /**
     * Indicate that the user processes guest orders.
     */
    public function orderOperator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => StaffRole::OrderOperator,
        ]);
    }
}
