<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('admin:create {email : The administrator email address} {--name=Administrator : Display name} {--password= : Password (prompted if omitted)}')]
#[Description('Create or promote an administrator account for production bootstrap.')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Please provide a valid email address.');

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?? '');

        if ($password === '') {
            $password = (string) $this->secret('Password (min 8 characters)');
            $confirmation = (string) $this->secret('Confirm password');

            if ($password !== $confirmation) {
                $this->error('Passwords do not match.');

                return self::FAILURE;
            }
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = (string) $this->option('name');
        $user->role = Role::Admin;
        $user->password = $password;
        $user->email_verified_at = now();
        $user->save();

        $this->info("Administrator ready: {$user->email}");

        return self::SUCCESS;
    }
}
