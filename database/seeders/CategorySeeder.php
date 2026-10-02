<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed a small-office category tree.
     */
    public function run(): void
    {
        $tree = [
            'IT Equipment' => [
                'Laptops',
                'Desktops & Monitors',
                'Peripherals',
                'Printing & Scanning',
                'Networking',
                'Servers & Storage',
            ],
            'Mobile Devices' => ['Smartphones', 'Tablets'],
            'Furniture' => ['Desks', 'Chairs', 'Cabinets', 'Meeting Room'],
            'Appliances' => ['Pantry', 'Air Conditioning'],
            'Software Licenses' => [],
        ];

        foreach ($tree as $parent => $children) {
            $parentModel = Category::updateOrCreate(
                ['slug' => Str::slug($parent)],
                ['name' => $parent, 'parent_id' => null],
            );

            foreach ($children as $child) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($child)],
                    ['name' => $child, 'parent_id' => $parentModel->id],
                );
            }
        }
    }
}
