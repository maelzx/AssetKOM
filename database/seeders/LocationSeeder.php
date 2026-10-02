<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Seed a small-office location tree.
     */
    public function run(): void
    {
        $tree = [
            'Head Office' => [
                'Reception',
                'Open Office',
                'Meeting Room',
                'Server Room',
                'Pantry',
                'Management Office',
            ],
            'Store Room' => [],
        ];

        $codes = [
            'Head Office' => 'HQ',
            'Reception' => 'HQ-REC',
            'Open Office' => 'HQ-OFF',
            'Meeting Room' => 'HQ-MTG',
            'Server Room' => 'HQ-SRV',
            'Pantry' => 'HQ-PAN',
            'Management Office' => 'HQ-MGT',
            'Store Room' => 'STORE',
        ];

        $addresses = [
            'Head Office' => 'Level 12, Menara Prestige, Jalan Pinang, 50450 Kuala Lumpur',
            'Store Room' => 'Level B1, Menara Prestige, Jalan Pinang, 50450 Kuala Lumpur',
        ];

        foreach ($tree as $parent => $children) {
            $parentModel = Location::updateOrCreate(
                ['code' => $codes[$parent]],
                ['name' => $parent, 'parent_id' => null, 'address' => $addresses[$parent] ?? null],
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
