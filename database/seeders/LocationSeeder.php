<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Seed a sample location tree.
     */
    public function run(): void
    {
        $tree = [
            'Head Office' => ['HQ — Floor 1', 'HQ — Floor 2'],
            'Warehouse' => ['Warehouse — Racking A', 'Warehouse — Racking B'],
            'Branch Office' => [],
        ];

        $codes = [
            'Head Office' => 'HQ',
            'HQ — Floor 1' => 'HQ-F1',
            'HQ — Floor 2' => 'HQ-F2',
            'Warehouse' => 'WH',
            'Warehouse — Racking A' => 'WH-A',
            'Warehouse — Racking B' => 'WH-B',
            'Branch Office' => 'BR',
        ];

        foreach ($tree as $parent => $children) {
            $parentModel = Location::updateOrCreate(
                ['code' => $codes[$parent]],
                ['name' => $parent, 'parent_id' => null],
            );

            foreach ($children as $child) {
                Location::updateOrCreate(
                    ['code' => $codes[$child]],
                    ['name' => $child, 'parent_id' => $parentModel->id],
                );
            }
        }
    }
}
