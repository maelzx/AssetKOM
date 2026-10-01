<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    /**
     * Seed a batch of demo assets across the category/location trees.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id');
        $locationIds = Location::pluck('id');
        $creatorId = User::query()->value('id');

        if ($categoryIds->isEmpty() || $locationIds->isEmpty()) {
            return;
        }

        Asset::factory()
            ->count(30)
            ->state(new Sequence(
                fn () => [
                    'category_id' => $categoryIds->random(),
                    'location_id' => $locationIds->random(),
                    'created_by' => $creatorId,
                ],
            ))
            ->create();
    }
}
