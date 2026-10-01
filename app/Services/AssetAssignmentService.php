<?php

namespace App\Services;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AssignmentStatus;
use App\Exceptions\AssetAssignmentException;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AssetAssignmentService
{
    public function __construct(private readonly AssetStatusTransition $transitions) {}

    /**
     * Check an asset out to a user or a location.
     *
     * Runs in a transaction with a row lock on the asset, and the unique
     * (asset_id, active) index acts as a database-level guarantee that only
     * one active assignment can exist per asset.
     *
     * @throws AssetAssignmentException
     */
    public function checkout(
        Asset $asset,
        Model $assignable,
        User $assignedBy,
        ?string $expectedReturnAt = null,
        ?AssetCondition $conditionOut = null,
        ?string $notes = null,
    ): AssetAssignment {
        return DB::transaction(function () use ($asset, $assignable, $assignedBy, $expectedReturnAt, $conditionOut, $notes): AssetAssignment {
            $locked = Asset::whereKey($asset->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->activeAssignment()->exists()) {
                throw new AssetAssignmentException(__('This asset is already checked out.'));
            }

            if (! in_array(AssetStatus::Assigned, $this->transitions->transitionsFor($locked->status), true)) {
                throw new AssetAssignmentException(
                    __('An asset with status ":status" cannot be checked out.', ['status' => $locked->status->label()])
                );
            }

            $assignment = $locked->assignments()->create([
                'assignable_type' => $assignable->getMorphClass(),
                'assignable_id' => $assignable->getKey(),
                'assigned_by' => $assignedBy->getKey(),
                'assigned_at' => now(),
                'expected_return_at' => $expectedReturnAt,
                'condition_out' => $conditionOut,
                'checkout_notes' => $notes,
                'status' => AssignmentStatus::Active,
                'active' => 1,
            ]);

            $locked->status = AssetStatus::Assigned;

            if ($assignable instanceof Location) {
                $locked->location_id = $assignable->getKey();
            }

            $locked->save();

            return $assignment;
        });
    }

    /**
     * Check an active assignment back in and free the asset.
     *
     * @throws AssetAssignmentException
     */
    public function checkin(
        AssetAssignment $assignment,
        User $checkedInBy,
        ?AssetCondition $conditionIn = null,
        ?string $notes = null,
    ): void {
        DB::transaction(function () use ($assignment, $conditionIn, $notes): void {
            $lockedAssignment = AssetAssignment::whereKey($assignment->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedAssignment->isActive()) {
                throw new AssetAssignmentException(__('This assignment has already been checked in.'));
            }

            $lockedAssignment->forceFill([
                'returned_at' => now(),
                'condition_in' => $conditionIn,
                'checkin_notes' => $notes,
                'status' => AssignmentStatus::Returned,
                'active' => null,
            ])->save();

            $lockedAsset = Asset::whereKey($lockedAssignment->asset_id)->lockForUpdate()->firstOrFail();

            if (in_array(AssetStatus::Available, $this->transitions->transitionsFor($lockedAsset->status), true)) {
                $lockedAsset->status = AssetStatus::Available;
                $lockedAsset->save();
            }
        });
    }
}
