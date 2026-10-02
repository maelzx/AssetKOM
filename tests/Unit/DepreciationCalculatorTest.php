<?php

namespace Tests\Unit;

use App\Enums\Currency;
use App\Models\Asset;
use App\Models\Setting;
use App\Services\DepreciationCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepreciationCalculatorTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_reducing_balance_uses_configured_rate(): void
    {
        Setting::set('depreciation_method', 'reducing_balance');
        Setting::set('depreciation_rate', 25, 'float');

        $calculator = new DepreciationCalculator;
        $asset = $this->asset(['salvage_value' => 0]);

        // 25% of 1200 in year one.
        $this->assertSame(300.0, $calculator->annualAmount($asset));
        $this->assertEqualsWithDelta(900.0, $calculator->bookValue($asset, CarbonImmutable::parse('2021-01-01')), 5.0);
        $this->assertEqualsWithDelta(675.0, $calculator->bookValue($asset, CarbonImmutable::parse('2022-01-01')), 5.0);
    }

    public function test_reducing_balance_without_a_rate_uses_double_declining(): void
    {
        Setting::set('depreciation_method', 'reducing_balance');
        Setting::set('depreciation_rate', 0, 'float');

        $calculator = new DepreciationCalculator;
        $asset = $this->asset(['salvage_value' => 0, 'useful_life_years' => 5]);

        // Double-declining on a 5-year life = 40% p.a.
        $this->assertSame(480.0, $calculator->annualAmount($asset));
    }

    public function test_as_of_date_changes_book_value(): void
    {
        $asset = $this->asset();

        $atPurchase = $this->calculator->bookValue($asset, CarbonImmutable::parse('2020-01-01'));
        $threeYears = $this->calculator->bookValue($asset, CarbonImmutable::parse('2023-01-01'));

        $this->assertGreaterThan($threeYears, $atPurchase);
    }
}
