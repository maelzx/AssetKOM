<?php

namespace App\Http\Controllers;

use App\Models\Asset;
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
            'qr' => AssetQrCode::dataUri($asset, 400),
        ])->setPaper('a4');

        return $pdf->download('label-'.$asset->asset_tag.'.pdf');
    }

    public function bulk(Request $request): Response
    {
        $status = $request->string('status')->toString();

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
            'qr' => AssetQrCode::dataUri($asset, 220),
        ]);

        $pdf = Pdf::loadView('labels.bulk', ['labels' => $labels])->setPaper('a4');

        return $pdf->download('asset-labels.pdf');
    }
}
