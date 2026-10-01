<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_access_reports(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/reports')->assertForbidden();
        $this->actingAs($staff)->get(route('exports.assets'))->assertForbidden();
    }

    public function test_manager_can_view_reports(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/reports')
            ->assertOk()
            ->assertSeeLivewire('reports.index');
    }

    public function test_manager_can_export_assets_as_csv(): void
    {
        $manager = User::factory()->manager()->create();
        $asset = Asset::factory()->create(['asset_tag' => 'AST-0042', 'name' => 'Exported Laptop']);

        $response = $this->actingAs($manager)->get(route('exports.assets'));

        $response->assertOk()->assertDownload('assets.csv');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Asset Tag', $csv);
        $this->assertStringContainsString('Exported Laptop', $csv);
        $this->assertStringContainsString('AST-0042', $csv);
    }

    public function test_manager_can_export_maintenance_and_assignments(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->get(route('exports.maintenance'))->assertOk()->assertDownload('maintenance.csv');
        $this->actingAs($manager)->get(route('exports.assignments'))->assertOk()->assertDownload('assignments.csv');
    }
}
