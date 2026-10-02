<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepreciationTest extends TestCase
{
    use RefreshDatabase;

    private function depreciableAsset(): Asset
    {
        return Asset::factory()->create([
            'purchase_cost' => 1000,
            'salvage_value' => 0,
            'useful_life_years' => 5,
            'purchase_date' => now()->subYears(2)->toDateString(),
        ]);
    }

    public function test_asset_detail_shows_depreciation(): void
    {
        $asset = $this->depreciableAsset();

        $this->actingAs(User::factory()->staff()->create())
            ->get('/assets/'.$asset->id)
            ->assertOk()
            ->assertSee('Current book value')
            ->assertSee('Annual depreciation');
    }

    public function test_asset_detail_prompts_when_not_depreciable(): void
    {
        $asset = Asset::factory()->create(['useful_life_years' => null]);

        $this->actingAs(User::factory()->staff()->create())
            ->get('/assets/'.$asset->id)
            ->assertOk()
            ->assertSee('to calculate depreciation');
    }

    public function test_reports_show_depreciation_summary(): void
    {
        $this->depreciableAsset();

        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/reports')
            ->assertOk()
            ->assertSee('Depreciation');
    }

    public function test_assets_export_includes_book_value(): void
    {
        $this->depreciableAsset();

        $manager = User::factory()->manager()->create();

        $csv = $this->actingAs($manager)->get(route('exports.assets'))->streamedContent();

        $this->assertStringContainsString('Book Value', $csv);
        $this->assertStringContainsString('Accumulated Depreciation', $csv);
    }

    public function test_reports_show_an_indicative_base_currency_total(): void
    {
        Asset::factory()->create([
            'currency' => 'MYR',
            'purchase_cost' => 1000,
            'salvage_value' => 0,
            'useful_life_years' => 5,
            'purchase_date' => now()->subYear()->toDateString(),
        ]);
        Asset::factory()->create([
            'currency' => 'USD',
            'purchase_cost' => 1000,
            'salvage_value' => 0,
            'useful_life_years' => 5,
            'purchase_date' => now()->subYear()->toDateString(),
        ]);

        $this->actingAs(User::factory()->manager()->create())
            ->get('/reports')
            ->assertOk()
            ->assertSee('Indicative total book value');
    }
}
