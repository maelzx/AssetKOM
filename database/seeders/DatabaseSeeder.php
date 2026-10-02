<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Settings are required in every environment.
        $this->call(SettingSeeder::class);

        // Demo accounts and sample data are local/testing only — never in production.
        if (app()->environment('production')) {
            return;
        }

        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            LocationSeeder::class,
            AssetSeeder::class,
            AssignmentSeeder::class,
            MaintenanceSeeder::class,
        ]);
    }
}
