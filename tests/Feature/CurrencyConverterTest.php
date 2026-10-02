<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Models\Setting;
use App\Services\CurrencyConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyConverterTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_currency_rate_is_one(): void
    {
        $this->assertSame(1.0, app(CurrencyConverter::class)->rate(Currency::MYR, Currency::MYR));
    }

    public function test_usd_to_myr_uses_the_configured_rate(): void
    {
        Setting::set('usd_to_myr_rate', 4.5, 'float');

        $this->assertSame(4.5, app(CurrencyConverter::class)->rate(Currency::USD, Currency::MYR));
    }

    public function test_myr_to_usd_is_the_inverse(): void
    {
        Setting::set('usd_to_myr_rate', 4.0, 'float');

        $this->assertSame(0.25, app(CurrencyConverter::class)->rate(Currency::MYR, Currency::USD));
    }

    public function test_convert_rounds_to_two_decimals(): void
    {
        Setting::set('usd_to_myr_rate', 4.3333, 'float');

        $this->assertSame(433.33, app(CurrencyConverter::class)->convert(100, Currency::USD, Currency::MYR));
    }
}
