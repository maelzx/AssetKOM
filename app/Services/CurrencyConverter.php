<?php

namespace App\Services;

use App\Enums\Currency;
use App\Models\Setting;

/**
 * Manual, current-rate currency conversion using the configured
 * `usd_to_myr_rate` setting (MYR per 1 USD). No historical rates are stored,
 * so converted figures are indicative only.
 */
class CurrencyConverter
{
    public function rate(Currency $from, Currency $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $usdToMyr = (float) Setting::get('usd_to_myr_rate', 4.70);

        if ($usdToMyr <= 0) {
            return 1.0;
        }

        return match (true) {
            $from === Currency::USD && $to === Currency::MYR => $usdToMyr,
            $from === Currency::MYR && $to === Currency::USD => 1 / $usdToMyr,
            default => 1.0,
        };
    }

    public function convert(float|int|string $amount, Currency $from, Currency $to): float
    {
        return round((float) $amount * $this->rate($from, $to), 2);
    }
}
