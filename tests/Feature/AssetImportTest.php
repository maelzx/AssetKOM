<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use App\Services\AssetCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AssetImportTest extends TestCase
{
    use RefreshDatabase;

    private function csvFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'assetcsv_');
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * @return array<string, string>
     */
    private function mapping(): array
    {
        return [
            'asset_tag' => 'Asset Tag',
            'name' => 'Name',
            'category' => 'Category',
            'location' => 'Location',
            'status' => 'Status',
            'purchase_cost' => 'Cost',
            'currency' => 'Currency',
        ];
    }

    public function test_preview_returns_headers_and_rows(): void
    {
        $path = $this->csvFile("Asset Tag,Name\nAST-1,Thing\n");

        $preview = app(AssetCsvImporter::class)->preview($path);

        $this->assertSame(['Asset Tag', 'Name'], $preview['headers']);
        $this->assertCount(1, $preview['preview']);
    }

    public function test_dry_run_reports_create_update_and_errors(): void
    {
        Asset::factory()->create(['asset_tag' => 'AST-2']);

        $path = $this->csvFile("Asset Tag,Name\nAST-1,New thing\nAST-2,Existing\n,\n");

        $report = app(AssetCsvImporter::class)->dryRun($path, $this->mapping());

        $this->assertSame(1, $report['summary']['create']);
        $this->assertSame(1, $report['summary']['update']);
        $this->assertSame(1, $report['summary']['errors']);
    }

    public function test_import_creates_and_is_idempotent_by_asset_tag(): void
    {
        $category = Category::factory()->create(['name' => 'Laptops']);
        $location = Location::factory()->create(['name' => 'Head Office']);

        $path = $this->csvFile(
            "Asset Tag,Name,Category,Location,Status,Cost,Currency\n".
            "AST-1,Dell,Laptops,Head Office,available,1200,MYR\n".
            "AST-2,HP,Monitors,Head Office,available,300,MYR\n"
        );

        $importer = app(AssetCsvImporter::class);

        $first = $importer->import($path, $this->mapping());

        $this->assertSame(2, $first['created']);
        $this->assertDatabaseCount('assets', 2);

        $asset = Asset::query()->where('asset_tag', 'AST-1')->firstOrFail();
        $this->assertSame($category->id, $asset->category_id);
        $this->assertSame($location->id, $asset->location_id);

        $second = $importer->import($path, $this->mapping());

        $this->assertSame(0, $second['created']);
        $this->assertSame(2, $second['updated']);
        $this->assertDatabaseCount('assets', 2);
    }

    public function test_rows_missing_required_fields_are_skipped(): void
    {
        $path = $this->csvFile("Asset Tag,Name\nAST-1,Good\n,\n");

        $result = app(AssetCsvImporter::class)->import($path, $this->mapping());

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['skipped']);
    }

    public function test_invalid_status_is_reported(): void
    {
        $path = $this->csvFile("Asset Tag,Name,Status\nAST-1,Thing,exploded\n");

        $report = app(AssetCsvImporter::class)->dryRun($path, $this->mapping());

        $this->assertSame(1, $report['summary']['errors']);
        $this->assertNotEmpty($report['rows'][0]['errors']);
    }

    public function test_import_page_requires_asset_management_permission(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/imports/assets')
            ->assertForbidden();

        $this->actingAs(User::factory()->manager()->create())
            ->get('/imports/assets')
            ->assertOk()
            ->assertSeeLivewire('imports.assets');
    }

    public function test_manager_can_import_through_the_component(): void
    {
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager);

        $csv = "Asset Tag,Name,Status,Currency\nAST-77,Imported Thing,available,MYR\n";

        $component = Volt::test('imports.assets')
            ->set('file', UploadedFile::fake()->createWithContent('assets.csv', $csv))
            ->call('parse')
            ->assertHasNoErrors();

        $component->call('dryRun')->assertHasNoErrors();
        $this->assertSame(1, $component->get('report')['summary']['create']);

        $component->call('import')->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'asset_tag' => 'AST-77',
            'name' => 'Imported Thing',
            'created_by' => $manager->id,
        ]);
    }
}
