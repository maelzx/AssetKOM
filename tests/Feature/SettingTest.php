<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_default_when_key_is_missing(): void
    {
        $this->assertSame('fallback', Setting::get('missing', 'fallback'));
    }

    public function test_set_and_get_round_trip_with_types(): void
    {
        Setting::set('org_name', 'Acme');
        Setting::set('usd_to_myr_rate', 4.75, 'float');
        Setting::set('feature_enabled', true, 'boolean');

        $this->assertSame('Acme', Setting::get('org_name'));
        $this->assertSame(4.75, Setting::get('usd_to_myr_rate'));
        $this->assertTrue(Setting::get('feature_enabled'));
    }

    public function test_cache_is_flushed_when_a_setting_changes(): void
    {
        Setting::set('org_name', 'First');
        $this->assertSame('First', Setting::get('org_name'));

        Setting::set('org_name', 'Second');

        $this->assertSame('Second', Setting::get('org_name'));
    }
}
