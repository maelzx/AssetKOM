<?php

namespace Tests\Unit;

use App\Enums\Currency;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_formats_myr_with_rm_symbol(): void
    {
        $this->assertSame('RM1,234.50', Money::format(1234.5, Currency::MYR));
    }

    public function test_formats_usd_with_dollar_symbol(): void
    {
        $this->assertSame('$1,000.00', Money::format(1000, Currency::USD));
    }

    public function test_defaults_to_myr_when_currency_is_null(): void
    {
        $this->assertSame('RM10.00', Money::format(10));
    }

    public function test_resolves_currency_from_string(): void
    {
        $this->assertSame(Currency::USD, Money::resolve('USD'));
        $this->assertSame(Currency::MYR, Money::resolve('MYR'));
    }

    public function test_unknown_currency_string_falls_back_to_myr(): void
    {
        $this->assertSame(Currency::MYR, Money::resolve('XYZ'));
    }
}
