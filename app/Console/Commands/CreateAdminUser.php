<?php

namespace App\Console\Commands;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-admin {email? : Email address of the administrator} {--name= : Name for a new administrator}')]
#[Description('Create a hotel administrator, or promote an existing staff account to administrator')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?? text(
            label: 'Email address',
            required: true,
            validate: ['email' => 'email'],
        );

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser !== null) {
            $existingUser->update(['role' => StaffRole::Admin]);

            $this->components->info("[{$email}] is now an administrator.");

            return self::SUCCESS;
        }

        User::query()->create([
            'name' => $this->option('name') ?? text(label: 'Name', required: true),
            'email' => $email,
            'password' => password(label: 'Password', required: true, validate: ['password' => 'min:8']),
            'role' => StaffRole::Admin,
        ]);

        $this->components->info("Administrator [{$email}] created.");

        return self::SUCCESS;
    }
}
