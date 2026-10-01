<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SettingsScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_staff_cannot_access_settings(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/settings')->assertForbidden();
    }

    public function test_manager_can_view_settings(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/settings')
            ->assertOk()
            ->assertSeeLivewire('settings.index');
    }

    public function test_manager_can_update_settings(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager);

        Volt::test('settings.index')
            ->set('org_name', 'Acme Assets')
            ->set('usd_to_myr_rate', 4.85)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Acme Assets', Setting::get('org_name'));
        $this->assertEquals(4.85, Setting::get('usd_to_myr_rate'));
    }

    public function test_settings_validation_rejects_unknown_currency(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager);

        Volt::test('settings.index')
            ->set('default_currency', 'XYZ')
            ->call('save')
            ->assertHasErrors(['default_currency']);
    }
}
