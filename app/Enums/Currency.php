<?php

namespace App\Enums;

enum Currency: string
{
    case MYR = 'MYR';
    case USD = 'USD';

    public function symbol(): string
    {
        return match ($this) {
            self::MYR => 'RM',
            self::USD => '$',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::MYR => 'Malaysian Ringgit',
            self::USD => 'US Dollar',
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
