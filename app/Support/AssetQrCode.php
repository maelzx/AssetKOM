<?php

namespace App\Support;

use App\Models\Asset;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Writer\PngWriter;

class AssetQrCode
{
    /**
     * The URL encoded into the QR code — the authenticated scan-to-view route.
     */
    public static function payload(Asset $asset): string
    {
        return route('scan.show', $asset->asset_tag);
    }

    /**
     * Render the asset QR code as a PNG data URI (safe for HTML and dompdf).
     */
    public static function dataUri(Asset $asset, int $size = 300, int $margin = 8): string
    {
        $qrCode = new EndroidQrCode(
            data: self::payload($asset),
            size: $size,
            margin: $margin,
        );

        return (new PngWriter)->write($qrCode)->getDataUri();
    }
}
