<?php

namespace Tests\Unit;

use App\Support\AssetBarcode;
use PHPUnit\Framework\TestCase;

class AssetBarcodeTest extends TestCase
{
    public function test_renders_a_png_data_uri(): void
    {
        $dataUri = AssetBarcode::dataUri('AST-0001');

        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
        $this->assertGreaterThan(0, strlen($dataUri));
    }
}
