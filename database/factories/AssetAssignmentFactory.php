<?php

namespace Database\Factories;

use App\Enums\AssignmentStatus;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetAssignment>
 */
class AssetAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'assignable_type' => User::class,
            'assignable_id' => User::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'expected_return_at' => now()->addDays(7)->toDateString(),
            'status' => AssignmentStatus::Active,
            'active' => 1,
        ];
    }

    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssignmentStatus::Returned,
            'active' => null,
            'returned_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'expected_return_at' => now()->subDays(3)->toDateString(),
        ]);
    }
}
