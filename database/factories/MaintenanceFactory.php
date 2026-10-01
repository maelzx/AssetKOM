<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Models\Asset;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Maintenance>
 */
class MaintenanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'type' => fake()->randomElement(MaintenanceType::cases()),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'vendor' => fake()->optional()->company(),
            'cost' => fake()->randomFloat(2, 20, 2000),
            'currency' => Currency::MYR,
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_at' => fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
            'performed_by' => null,
            'created_by' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MaintenanceStatus::InProgress,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MaintenanceStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_at' => now()->subDays(5)->toDateString(),
        ]);
    }
}
