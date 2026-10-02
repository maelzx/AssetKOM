<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Enums\Role;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\User;
use App\Services\MaintenanceService;
use Illuminate\Database\Seeder;

class MaintenanceSeeder extends Seeder
{
    /**
     * Seed realistic maintenance records for the small office.
     */
    public function run(): void
    {
        $actor = User::query()->where('role', Role::Admin->value)->first();

        if (! $actor) {
            return;
        }

        $this->record('%Aircond (Open Office)%', 'Annual aircond servicing (Open Office)', [
            'type' => MaintenanceType::Preventive,
            'description' => 'Chemical wash and gas top-up.',
            'vendor' => 'Daikin Service Centre',
            'cost' => 350,
            'status' => MaintenanceStatus::Completed,
            'scheduled_at' => now()->subMonth()->toDateString(),
            'completed_at' => now()->subMonth()->addDay(),
        ], $actor);

        $this->record('%PowerEdge T150%', 'Server firmware update and disk check', [
            'type' => MaintenanceType::Preventive,
            'description' => 'Applied BIOS/firmware updates and ran SMART disk checks.',
            'vendor' => 'Dell ProSupport',
            'cost' => 0,
            'status' => MaintenanceStatus::Completed,
            'scheduled_at' => now()->subWeeks(2)->toDateString(),
            'completed_at' => now()->subWeeks(2)->addDay(),
        ], $actor);

        $this->record('%LaserJet Pro M404dn%', 'Printer preventive maintenance', [
            'type' => MaintenanceType::Preventive,
            'description' => 'Clean rollers, replace toner, check fuser.',
            'vendor' => 'HP Service',
            'cost' => 180,
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_at' => now()->addDays(14)->toDateString(),
        ], $actor);

        $this->record('%EB-FH06%', 'Projector lamp inspection', [
            'type' => MaintenanceType::Inspection,
            'description' => 'Check lamp hours and filter.',
            'vendor' => 'Epson Service',
            'cost' => 0,
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_at' => now()->subDays(5)->toDateString(),
        ], $actor);

        $this->startCopierRepair($actor);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(string $assetNameLike, string $title, array $attributes, User $actor): void
    {
        $asset = Asset::query()->where('name', 'like', $assetNameLike)->first();

        if (! $asset) {
            return;
        }

        Maintenance::create(array_merge([
            'asset_id' => $asset->id,
            'title' => $title,
            'currency' => Currency::MYR,
            'created_by' => $actor->id,
            'performed_by' => $actor->id,
        ], $attributes));
    }

    private function startCopierRepair(User $actor): void
    {
        $copier = Asset::query()->where('name', 'like', '%imageRUNNER%')->first();

        if (! $copier) {
            return;
        }

        $record = Maintenance::create([
            'asset_id' => $copier->id,
            'type' => MaintenanceType::Corrective,
            'title' => 'Drum unit replacement',
            'description' => 'Print quality deteriorated; replacing the drum unit.',
            'vendor' => 'Canon Service Centre',
            'cost' => 850,
            'currency' => Currency::MYR,
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_at' => now()->toDateString(),
            'created_by' => $actor->id,
            'performed_by' => $actor->id,
        ]);

        app(MaintenanceService::class)->start($record);
    }
}
