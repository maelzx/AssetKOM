<?php

namespace Database\Factories;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\Currency;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchaseCost = fake()->randomFloat(2, 100, 10000);

        return [
            'asset_tag' => strtoupper(fake()->unique()->bothify('AST-####')),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'category_id' => null,
            'location_id' => null,
            'status' => AssetStatus::Available,
            'condition' => fake()->randomElement(AssetCondition::cases()),
            'serial_number' => strtoupper(fake()->unique()->bothify('SN########')),
            'manufacturer' => fake()->company(),
            'model' => strtoupper(fake()->bothify('MDL-####')),
            'purchase_date' => fake()->dateTimeBetween('-5 years')->format('Y-m-d'),
            'purchase_cost' => $purchaseCost,
            'currency' => Currency::MYR,
            'salvage_value' => round($purchaseCost * 0.1, 2),
            'useful_life_years' => fake()->numberBetween(3, 10),
            'warranty_expiry' => fake()->dateTimeBetween('now', '+3 years')->format('Y-m-d'),
            'supplier' => fake()->company(),
            'custom_fields' => [],
            'created_by' => null,
        ];
    }

    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }

    public function atLocation(Location $location): static
    {
        return $this->state(fn (array $attributes) => [
            'location_id' => $location->id,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    public function status(AssetStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
