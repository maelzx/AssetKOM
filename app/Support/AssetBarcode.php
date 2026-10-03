<?php

namespace App\Support;

use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Renders a 1D Code 128 barcode (for asset tags) as a PNG data URI,
 * suitable for both HTML and dompdf labels.
 */
class AssetBarcode
{
    public static function dataUri(string $code, int $widthFactor = 2, int $height = 60): string
    {
        $png = (new BarcodeGeneratorPNG)->getBarcode(
            $code,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            $widthFactor,
            $height,
        );

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
