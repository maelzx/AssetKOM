<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\Role;
use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use App\Services\AssetAssignmentService;
use Illuminate\Database\Seeder;

class AssignmentSeeder extends Seeder
{
    /**
     * Check a few available assets out to users and locations.
     */
    public function run(): void
    {
        $service = app(AssetAssignmentService::class);

        $actor = User::where('role', Role::Admin->value)->first() ?? User::first();
        $users = User::all();
        $locations = Location::all();

        if (! $actor || $users->isEmpty() || $locations->isEmpty()) {
            return;
        }

        $assets = Asset::where('status', AssetStatus::Available->value)->take(6)->get();

        foreach ($assets as $index => $asset) {
            $assignable = $index % 2 === 0 ? $users->random() : $locations->random();

            $service->checkout($asset, $assignable, $actor, now()->addWeeks(2)->toDateString());
        }
    }
}
