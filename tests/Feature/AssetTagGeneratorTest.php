<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Setting;
use App\Services\AssetTagGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTagGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('asset_tag_prefix', 'AST');
        Setting::set('asset_tag_sequence', 0, 'integer');
    }

    public function test_generates_sequential_tags_with_prefix(): void
    {
        $generator = app(AssetTagGenerator::class);

        $this->assertSame('AST-0001', $generator->generate());
        $this->assertSame('AST-0002', $generator->generate());
    }

    public function test_skips_tags_that_already_exist(): void
    {
        Asset::factory()->create(['asset_tag' => 'AST-0001']);

        $this->assertSame('AST-0002', app(AssetTagGenerator::class)->generate());
    }

    public function test_asset_auto_generates_tag_on_create(): void
    {
        $asset = Asset::create(['name' => 'Unnamed asset']);

        $this->assertSame('AST-0001', $asset->asset_tag);
    }

    public function test_prefix_setting_is_honoured(): void
    {
        Setting::set('asset_tag_prefix', 'IT');

        $this->assertSame('IT-0001', app(AssetTagGenerator::class)->generate());
    }
}
