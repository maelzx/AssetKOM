<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_the_dashboard(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Total assets');
    }

    public function test_dashboard_reports_asset_counts(): void
    {
        Asset::factory()->count(3)->create(['status' => AssetStatus::Available]);
        Asset::factory()->create(['status' => AssetStatus::Assigned]);

        $this->actingAs(User::factory()->staff()->create());

        Volt::test('dashboard')
            ->assertSee('Total assets')
            ->assertSee('4')
            ->assertSee('Available');
    }

    public function test_dashboard_surfaces_maintenance_due(): void
    {
        Maintenance::factory()->overdue()->create(['title' => 'Overdue service']);

        $this->actingAs(User::factory()->staff()->create());

        Volt::test('dashboard')
            ->assertSee('Maintenance due')
            ->assertSee('Overdue service');
    }
}
