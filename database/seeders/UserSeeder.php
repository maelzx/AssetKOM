<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed one user per role for local development.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'System Administrator', 'email' => 'admin@assetkom.test', 'role' => Role::Admin],
            ['name' => 'Asset Manager', 'email' => 'manager@assetkom.test', 'role' => Role::Manager],
            ['name' => 'General Staff', 'email' => 'staff@assetkom.test', 'role' => Role::Staff],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'email_verified_at' => now(),
                    'password' => 'password',
                ],
            );
        }
    }
}
