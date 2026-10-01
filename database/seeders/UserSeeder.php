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
            ['name' => 'System Administrator', 'email' => 'admin@assetkom.test', 'role' => Role::Admin, 'notify' => true],
            ['name' => 'Asset Manager', 'email' => 'manager@assetkom.test', 'role' => Role::Manager, 'notify' => true],
            ['name' => 'General Staff', 'email' => 'staff@assetkom.test', 'role' => Role::Staff, 'notify' => false],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'email_verified_at' => now(),
                    'password' => 'password',
                    'notify_warranty_expiry' => $user['notify'],
                    'notify_maintenance_due' => $user['notify'],
                    'notify_overdue_assignments' => $user['notify'],
                ],
            );
        }
    }
}
