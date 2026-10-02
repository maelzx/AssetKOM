<?php

namespace Database\Seeders;

use App\Enums\AssetCondition;
use App\Enums\Role;
use App\Models\Asset;
use App\Models\User;
use App\Services\AssetAssignmentService;
use Illuminate\Database\Seeder;

class AssignmentSeeder extends Seeder
{
    /**
     * Issue laptops and mobile devices to staff.
     */
    public function run(): void
    {
        $service = app(AssetAssignmentService::class);
        $actor = User::query()->where('role', Role::Admin->value)->first();
        $manager = User::query()->where('role', Role::Manager->value)->first();

        $staff = User::query()
            ->where('role', Role::Staff->value)
            ->orderBy('name')
            ->get()
            ->values();

        if (! $actor || ! $manager || $staff->count() < 4) {
            return;
        }

        $issue = function (string $assetName, User $user) use ($service, $actor): void {
            $asset = Asset::query()->where('name', $assetName)->first();

            if ($asset) {
                $service->checkout(
                    $asset,
                    $user,
                    $actor,
                    now()->addYear()->toDateString(),
                    AssetCondition::Good,
                    'Issued during onboarding.',
                );
            }
        };

        // Laptops
        $issue('Dell Latitude 5440 Business Laptop', $staff[0]);
        $issue('HP ProBook 450 G9 Laptop', $staff[1]);
        $issue('Lenovo ThinkPad T14 Gen 3', $staff[2]);
        $issue('Apple MacBook Air M2 13"', $manager);
        $issue('Acer Aspire 5 (Front Desk)', $staff[3]);

        // Mobile devices
        $issue('Apple iPhone 15 128GB', $manager);
        $issue('Samsung Galaxy S24', $staff->get(4) ?? $staff[0]);
        $issue('Xiaomi Redmi Note 13 (Backup)', $staff->get(5) ?? $staff[1]);
        $issue('Apple iPad 10.9" (Sales)', $staff->get(4) ?? $staff[0]);
    }
}
