<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\MaintenanceStatus;
use App\Exceptions\MaintenanceException;
use App\Models\Asset;
use App\Models\Maintenance;
use Illuminate\Support\Facades\DB;

class MaintenanceService
{
    public function __construct(
        private readonly MaintenanceStatusTransition $transitions,
        private readonly AssetStatusTransition $assetTransitions,
    ) {}

    /**
     * Move a maintenance record into progress and place the asset under maintenance.
     *
     * @throws MaintenanceException
     */
    public function start(Maintenance $maintenance): Maintenance
    {
        return DB::transaction(function () use ($maintenance): Maintenance {
            $locked = Maintenance::whereKey($maintenance->getKey())->lockForUpdate()->firstOrFail();
            $this->transitions->assertCanTransition($locked->status, MaintenanceStatus::InProgress);

            $asset = Asset::whereKey($locked->asset_id)->lockForUpdate()->firstOrFail();

            if ($asset->activeAssignment()->exists()) {
                throw new MaintenanceException(__('Check the asset in before starting maintenance.'));
            }

            $conflict = Maintenance::query()
                ->where('asset_id', $locked->asset_id)
                ->where('status', MaintenanceStatus::InProgress->value)
                ->whereKeyNot($locked->getKey())
                ->exists();

            if ($conflict) {
                throw new MaintenanceException(__('Another maintenance record is already in progress for this asset.'));
            }

            if ($asset->status !== AssetStatus::Maintenance) {
                if (! in_array(AssetStatus::Maintenance, $this->assetTransitions->transitionsFor($asset->status), true)) {
                    throw new MaintenanceException(
                        __('An asset with status ":status" cannot enter maintenance.', ['status' => $asset->status->label()])
                    );
                }

                $asset->status = AssetStatus::Maintenance;
                $asset->save();
            }

            $locked->status = MaintenanceStatus::InProgress;
            $locked->save();

            return $locked;
        });
    }

    /**
     * Mark a maintenance record complete and free the asset.
     *
     * @throws MaintenanceException
     */
    public function complete(Maintenance $maintenance, ?string $notes = null): Maintenance
    {
        return DB::transaction(function () use ($maintenance, $notes): Maintenance {
            $locked = Maintenance::whereKey($maintenance->getKey())->lockForUpdate()->firstOrFail();
            $this->transitions->assertCanTransition($locked->status, MaintenanceStatus::Completed);

            $locked->status = MaintenanceStatus::Completed;
            $locked->completed_at = now();

            if ($notes !== null) {
                $locked->notes = trim((string) ($locked->notes ? $locked->notes."\n".$notes : $notes));
            }

            $locked->save();

            $this->releaseAsset($locked);

            return $locked;
        });
    }

    /**
     * Cancel an open maintenance record and free the asset.
     *
     * @throws MaintenanceException
     */
    public function cancel(Maintenance $maintenance, ?string $notes = null): Maintenance
    {
        return DB::transaction(function () use ($maintenance, $notes): Maintenance {
            $locked = Maintenance::whereKey($maintenance->getKey())->lockForUpdate()->firstOrFail();
            $this->transitions->assertCanTransition($locked->status, MaintenanceStatus::Cancelled);

            $locked->status = MaintenanceStatus::Cancelled;

            if ($notes !== null) {
                $locked->notes = trim((string) ($locked->notes ? $locked->notes."\n".$notes : $notes));
            }

            $locked->save();

            $this->releaseAsset($locked);

            return $locked;
        });
    }

    /**
     * Return the asset to available, but only when no other maintenance
     * record is still in progress for it.
     */
    protected function releaseAsset(Maintenance $maintenance): void
    {
        $stillInProgress = Maintenance::query()
            ->where('asset_id', $maintenance->asset_id)
            ->where('status', MaintenanceStatus::InProgress->value)
            ->whereKeyNot($maintenance->getKey())
            ->exists();

        if ($stillInProgress) {
            return;
        }

        $asset = Asset::whereKey($maintenance->asset_id)->lockForUpdate()->firstOrFail();

        if ($asset->status === AssetStatus::Maintenance
            && in_array(AssetStatus::Available, $this->assetTransitions->transitionsFor($asset->status), true)) {
            $asset->status = AssetStatus::Available;
            $asset->save();
        }
    }
}
