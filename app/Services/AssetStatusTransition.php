<?php

namespace App\Services;

use App\Enums\AssetStatus;
use InvalidArgumentException;

/**
 * Single source of truth for asset status transitions.
 *
 * Every feature that changes an asset's status (assignments, maintenance,
 * retirement, loss) must route through here so the lifecycle stays consistent.
 */
class AssetStatusTransition
{
    /**
     * Statuses an asset may be created with. All other statuses are
     * lifecycle-driven (assignment / maintenance / retirement flows).
     *
     * @var array<int, string>
     */
    public const INITIAL_STATUSES = ['available', 'retired', 'lost'];

    /**
     * Allowed target statuses keyed by the current status.
     *
     * @var array<string, array<int, AssetStatus>>
     */
    protected const TRANSITIONS = [
        'available' => [AssetStatus::Assigned, AssetStatus::Maintenance, AssetStatus::Retired, AssetStatus::Lost],
        'assigned' => [AssetStatus::Available, AssetStatus::Maintenance, AssetStatus::Lost],
        'maintenance' => [AssetStatus::Available, AssetStatus::Retired, AssetStatus::Lost],
        'retired' => [],
        'lost' => [AssetStatus::Available],
    ];

    /**
     * @return array<int, AssetStatus>
     */
    public function transitionsFor(AssetStatus $from): array
    {
        return self::TRANSITIONS[$from->value] ?? [];
    }

    public function canTransition(AssetStatus $from, AssetStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return in_array($to, $this->transitionsFor($from), true);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function assertCanTransition(AssetStatus $from, AssetStatus $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(
                sprintf('Cannot transition asset status from "%s" to "%s".', $from->value, $to->value)
            );
        }
    }
}
