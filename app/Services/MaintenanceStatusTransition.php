<?php

namespace App\Services;

use App\Enums\MaintenanceStatus;
use InvalidArgumentException;

/**
 * Single source of truth for maintenance record status transitions.
 */
class MaintenanceStatusTransition
{
    /**
     * @var array<string, array<int, MaintenanceStatus>>
     */
    protected const TRANSITIONS = [
        'scheduled' => [MaintenanceStatus::InProgress, MaintenanceStatus::Completed, MaintenanceStatus::Cancelled],
        'in_progress' => [MaintenanceStatus::Completed, MaintenanceStatus::Cancelled],
        'completed' => [],
        'cancelled' => [],
    ];

    /**
     * @return array<int, MaintenanceStatus>
     */
    public function transitionsFor(MaintenanceStatus $from): array
    {
        return self::TRANSITIONS[$from->value] ?? [];
    }

    public function canTransition(MaintenanceStatus $from, MaintenanceStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return in_array($to, $this->transitionsFor($from), true);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function assertCanTransition(MaintenanceStatus $from, MaintenanceStatus $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new InvalidArgumentException(
                sprintf('Cannot transition maintenance from "%s" to "%s".', $from->value, $to->value)
            );
        }
    }
}
