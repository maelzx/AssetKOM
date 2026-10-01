<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Maintenance;
use Illuminate\Database\Seeder;

class MaintenanceSeeder extends Seeder
{
    /**
     * Seed a mix of scheduled and completed maintenance records.
     */
    public function run(): void
    {
        $assets = Asset::query()->inRandomOrder()->take(10)->get();

        if ($assets->isEmpty()) {
            return;
        }

        foreach ($assets as $asset) {
            Maintenance::factory()->create(['asset_id' => $asset->id]);

            if ($asset->status !== AssetStatus::Maintenance) {
                Maintenance::factory()->completed()->create(['asset_id' => $asset->id]);
            }
        }
    }
}
