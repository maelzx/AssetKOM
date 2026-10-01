<?php

namespace Tests\Unit;

use App\Enums\Currency;
use App\Models\Asset;
use App\Services\DepreciationCalculator;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DepreciationCalculatorTest extends TestCase
{
    private DepreciationCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new DepreciationCalculator;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function asset(array $attributes = []): Asset
    {
        return new Asset(array_merge([
            'purchase_cost' => 1200,
            'salvage_value' => 200,
            'useful_life_years' => 5,
            'purchase_date' => '2020-01-01',
            'currency' => Currency::MYR,
        ], $attributes));
    }

    public function test_annual_amount_is_depreciable_base_over_life(): void
    {
        $this->assertSame(200.0, $this->calculator->annualAmount($this->asset()));
    }

    public function test_book_value_equals_cost_at_purchase(): void
    {
        $this->assertSame(
            1200.0,
            $this->calculator->bookValue($this->asset(), CarbonImmutable::parse('2020-01-01'))
        );
    }

    public function test_book_value_decreases_after_a_year(): void
    {
        $this->assertEqualsWithDelta(
            1000.0,
            $this->calculator->bookValue($this->asset(), CarbonImmutable::parse('2021-01-01')),
            1.0
        );
    }

    public function test_book_value_never_drops_below_salvage(): void
    {
        $this->assertSame(
            200.0,
            $this->calculator->bookValue($this->asset(), CarbonImmutable::parse('2035-01-01'))
        );
    }

    public function test_future_purchase_date_depreciates_nothing(): void
    {
        $asset = $this->asset(['purchase_date' => '2030-01-01']);

        $this->assertSame(1200.0, $this->calculator->bookValue($asset, CarbonImmutable::parse('2025-01-01')));
    }

    public function test_not_depreciable_without_required_fields(): void
    {
        $this->assertFalse($this->calculator->isDepreciable($this->asset(['useful_life_years' => null])));
        $this->assertNull($this->calculator->bookValue($this->asset(['purchase_cost' => null])));
    }

    public function test_schedule_has_one_row_per_year_and_reaches_salvage(): void
    {
        $schedule = $this->calculator->schedule($this->asset());

        $this->assertCount(5, $schedule);
        $this->assertSame(200.0, $schedule[0]['depreciation']);
        $this->assertSame(200.0, $schedule[4]['closing']);
    }
}
