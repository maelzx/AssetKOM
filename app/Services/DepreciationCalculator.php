<?php

namespace App\Services;

use App\Models\Asset;
use Carbon\CarbonInterface;

/**
 * Straight-line depreciation.
 *
 * Depreciable base = purchase cost − salvage value, spread evenly across the
 * useful life. Book value is clamped so it never falls below salvage value.
 */
class DepreciationCalculator
{
    public function isDepreciable(Asset $asset): bool
    {
        return $asset->purchase_cost !== null
            && (float) $asset->purchase_cost > 0
            && $asset->useful_life_years !== null
            && $asset->useful_life_years > 0
            && $asset->purchase_date !== null;
    }

    public function depreciableBase(Asset $asset): ?float
    {
        if (! $this->isDepreciable($asset)) {
            return null;
        }

        return max(0.0, (float) $asset->purchase_cost - (float) ($asset->salvage_value ?? 0));
    }

    public function annualAmount(Asset $asset): ?float
    {
        $base = $this->depreciableBase($asset);

        if ($base === null) {
            return null;
        }

        return round($base / $asset->useful_life_years, 2);
    }

    public function accumulated(Asset $asset, ?CarbonInterface $asOf = null): ?float
    {
        $base = $this->depreciableBase($asset);

        if ($base === null) {
            return null;
        }

        $asOf ??= now();

        $accumulated = $this->elapsedYears($asset, $asOf) / $asset->useful_life_years * $base;

        return round(min($accumulated, $base), 2);
    }

    public function bookValue(Asset $asset, ?CarbonInterface $asOf = null): ?float
    {
        $accumulated = $this->accumulated($asset, $asOf);

        if ($accumulated === null) {
            return null;
        }

        return round((float) $asset->purchase_cost - $accumulated, 2);
    }

    /**
     * Year-by-year straight-line schedule for display.
     *
     * @return array<int, array{year: int, opening: float, depreciation: float, accumulated: float, closing: float}>
     */
    public function schedule(Asset $asset): array
    {
        $base = $this->depreciableBase($asset);

        if ($base === null) {
            return [];
        }

        $annual = $base / $asset->useful_life_years;
        $opening = (float) $asset->purchase_cost;
        $accumulated = 0.0;
        $rows = [];

        for ($year = 1; $year <= $asset->useful_life_years; $year++) {
            $depreciation = min($annual, $base - $accumulated);
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
