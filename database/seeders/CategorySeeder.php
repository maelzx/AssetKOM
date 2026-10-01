<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed a general-purpose category tree.
     */
    public function run(): void
    {
        $tree = [
            'Electronics' => ['Laptops', 'Desktops', 'Monitors', 'Peripherals', 'Networking'],
            'Furniture' => ['Desks', 'Chairs', 'Storage'],
            'Vehicles' => ['Cars', 'Motorcycles'],
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
