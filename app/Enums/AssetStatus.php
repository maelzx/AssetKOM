<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Available = 'available';
    case Assigned = 'assigned';
    case Maintenance = 'maintenance';
    case Retired = 'retired';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Assigned => 'Assigned',
            self::Maintenance => 'Maintenance',
            self::Retired => 'Retired',
            self::Lost => 'Lost',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'bg-green-100 text-green-800',
            self::Assigned => 'bg-blue-100 text-blue-800',
            self::Maintenance => 'bg-yellow-100 text-yellow-800',
            self::Retired => 'bg-gray-100 text-gray-800',
            self::Lost => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Whether the asset is still part of active inventory.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Available, self::Assigned, self::Maintenance], true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
