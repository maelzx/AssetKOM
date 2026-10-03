<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Setting;
use App\Support\AssetBarcode;
use App\Support\AssetQrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LabelController extends Controller
{
    public function single(Asset $asset): Response
    {
        $asset->loadMissing(['category.parent', 'location.parent']);

        $pdf = Pdf::loadView('labels.single', [
            'asset' => $asset,
            'barcode' => AssetBarcode::dataUri($asset->asset_tag),
            'qr' => $this->qrEnabled() ? AssetQrCode::dataUri($asset, 300) : null,
        ])->setPaper('a4');

        return $pdf->download('label-'.$asset->asset_tag.'.pdf');
    }

    public function bulk(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $qrEnabled = $this->qrEnabled();

        $assets = Asset::query()
            ->with(['category.parent', 'location.parent'])
            ->when($request->integer('category'), fn ($query, $category) => $query->where('category_id', $category))
            ->when($request->integer('location'), fn ($query, $location) => $query->where('location_id', $location))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('asset_tag')
            ->limit(300)
            ->get();

        $labels = $assets->map(fn (Asset $asset): array => [
            'asset' => $asset,
            'barcode' => AssetBarcode::dataUri($asset->asset_tag),
            'qr' => $qrEnabled ? AssetQrCode::dataUri($asset, 200) : null,
        ]);

        $pdf = Pdf::loadView('labels.bulk', ['labels' => $labels])->setPaper('a4');

        return $pdf->download('asset-labels.pdf');
    }

    private function qrEnabled(): bool
    {
        return (bool) Setting::get('label_qr_enabled', false);
    }
}
