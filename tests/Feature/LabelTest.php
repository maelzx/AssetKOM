<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use App\Support\AssetQrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_code_payload_points_to_the_scan_route(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'AST-0007']);

        $this->assertStringContainsString('/a/AST-0007', AssetQrCode::payload($asset));
    }

    public function test_qr_code_renders_as_png_data_uri(): void
    {
        $asset = Asset::factory()->create();

        $this->assertStringStartsWith('data:image/png;base64,', AssetQrCode::dataUri($asset));
    }

    public function test_authenticated_scan_redirects_to_the_asset(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'AST-0008']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('scan.show', $asset->asset_tag))
            ->assertRedirect(route('assets.show', $asset));
    }

    public function test_guests_cannot_scan(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'AST-0009']);

        $this->get(route('scan.show', $asset->asset_tag))->assertRedirect(route('login'));
    }

    public function test_labels_page_renders(): void
    {
        Asset::factory()->count(2)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->get('/labels')
            ->assertOk()
            ->assertSeeLivewire('labels.index')
            ->assertSee('data:image/png', false);
    }

    public function test_single_label_pdf_downloads(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'AST-0101']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('labels.single', $asset))
            ->assertOk()
            ->assertDownload('label-AST-0101.pdf');
    }

    public function test_bulk_labels_pdf_downloads(): void
    {
        Asset::factory()->count(4)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('labels.bulk'))
            ->assertOk()
            ->assertDownload('asset-labels.pdf');
    }

    public function test_bulk_labels_respects_category_filter(): void
    {
        $category = Category::factory()->create();
        Asset::factory()->create(['category_id' => $category->id]);
        Asset::factory()->create(['category_id' => null]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('labels.bulk', ['category' => $category->id]))
            ->assertOk()
            ->assertDownload('asset-labels.pdf');
    }
}
