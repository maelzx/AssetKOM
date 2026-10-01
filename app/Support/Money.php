<?php

namespace App\Support;

use App\Enums\Currency;

final class Money
{
    /**
     * Format an amount with its currency symbol, e.g. "RM1,234.00" or "$1,234.00".
     */
    public static function format(float|int|string|null $amount, Currency|string|null $currency = null): string
    {
        $currency = self::resolve($currency);

        return $currency->symbol().number_format((float) $amount, 2);
    }

    public static function resolve(Currency|string|null $currency): Currency
    {
        if ($currency instanceof Currency) {
            return $currency;
        }

        if (is_string($currency)) {
            return Currency::tryFrom($currency) ?? Currency::MYR;
        }

        return Currency::MYR;
    }
}
