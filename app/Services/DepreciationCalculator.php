<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Setting;
use Carbon\CarbonInterface;

/**
 * Depreciation calculation.
 *
 * Method is taken from the `depreciation_method` setting:
 *  - straight_line:    (cost − salvage) spread evenly across the useful life.
 *  - reducing_balance: opening book value × rate each year, floored at salvage.
 *    The rate comes from the `depreciation_rate` setting (% p.a.); if unset,
 *    double-declining (2 / useful life) is used.
 *
 * Values are calculated on demand (never stored), so they always reflect the
 * supplied "as of" date.
 */
class DepreciationCalculator
{
    private ?string $method = null;

    private ?float $rate = null;

    public function isDepreciable(Asset $asset): bool
    {
        return $asset->purchase_cost !== null
            && (float) $asset->purchase_cost > 0
            && $asset->useful_life_years !== null
            && $asset->useful_life_years > 0
            && $asset->purchase_date !== null;
    }

    public function method(): string
    {
        return $this->method ??= (string) Setting::get('depreciation_method', 'straight_line');
    }

    /**
     * First-year depreciation (for display).
     */
    public function annualAmount(Asset $asset): ?float
    {
        if (! $this->isDepreciable($asset)) {
            return null;
        }

        $cost = (float) $asset->purchase_cost;
        $salvage = (float) ($asset->salvage_value ?? 0);

        if ($this->method() === 'reducing_balance') {
            return round(min($cost - $salvage, $cost * $this->effectiveRate($asset)), 2);
        }

        return round(($cost - $salvage) / $asset->useful_life_years, 2);
    }

    public function bookValue(Asset $asset, ?CarbonInterface $asOf = null): ?float
    {
        if (! $this->isDepreciable($asset)) {
            return null;
        }

        $asOf ??= now();
        $cost = (float) $asset->purchase_cost;
        $salvage = (float) ($asset->salvage_value ?? 0);
        $years = $this->elapsedYears($asset, $asOf);

        $book = $this->method() === 'reducing_balance'
            ? $this->reducingBalanceBook($cost, $years, $asset)
            : $cost - ($cost - $salvage) * min(1.0, $years / $asset->useful_life_years);

        return round(max($salvage, $book), 2);
    }

    public function accumulated(Asset $asset, ?CarbonInterface $asOf = null): ?float
    {
        $book = $this->bookValue($asset, $asOf);

        if ($book === null) {
            return null;
        }

        return round((float) $asset->purchase_cost - $book, 2);
    }

    /**
     * Year-by-year schedule for the configured method.
     *
     * @return array<int, array{year: int, opening: float, depreciation: float, accumulated: float, closing: float}>
     */
    public function schedule(Asset $asset): array
    {
        if (! $this->isDepreciable($asset)) {
            return [];
        }

        $cost = (float) $asset->purchase_cost;
        $salvage = (float) ($asset->salvage_value ?? 0);
        $rate = $this->effectiveRate($asset);

        if ($this->method() === 'reducing_balance') {
            $annual = fn (float $opening): float => min($opening - $salvage, $opening * $rate);
        } else {
            $straight = ($cost - $salvage) / $asset->useful_life_years;
            $annual = fn (float $opening) => $straight;
        }

        $opening = $cost;
        $accumulated = 0.0;
        $rows = [];

        for ($year = 1; $year <= $asset->useful_life_years; $year++) {
            $depreciation = max(0.0, $annual($opening));
            $accumulated += $depreciation;
            $closing = $opening - $depreciation;

            $rows[] = [
                'year' => $year,
                'opening' => round($opening, 2),
                'depreciation' => round($depreciation, 2),
                'accumulated' => round($accumulated, 2),
                'closing' => round($closing, 2),
            ];

            $opening = $closing;
        }

        return $rows;
    }

    protected function reducingBalanceBook(float $cost, float $years, Asset $asset): float
    {
        return $cost * ((1 - $this->effectiveRate($asset)) ** $years);
    }

    /**
     * Annual reducing-balance rate: configured percent, else double-declining.
     */
    protected function effectiveRate(Asset $asset): float
    {
        $configured = $this->rate ??= $this->configuredRate();

        if ($configured !== null && $configured > 0) {
            return min($configured / 100, 1.0);
        }

        return min(2 / $asset->useful_life_years, 1.0);
    }

    protected function configuredRate(): ?float
    {
        $rate = Setting::get('depreciation_rate');

        return is_numeric($rate) ? (float) $rate : null;
    }

    /**
     * Elapsed full/partial years since purchase, capped at the useful life.
     */
    protected function elapsedYears(Asset $asset, CarbonInterface $asOf): float
    {
        if ($asset->purchase_date->greaterThan($asOf)) {
            return 0.0;
        }

        $years = $asset->purchase_date->diffInDays($asOf) / 365.25;

        return max(0.0, min($years, (float) $asset->useful_life_years));
    }
}
