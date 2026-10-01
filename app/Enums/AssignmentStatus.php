<?php

namespace App\Enums;

enum AssignmentStatus: string
{
    case Active = 'active';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Returned => 'Returned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'bg-blue-100 text-blue-800',
            self::Returned => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
