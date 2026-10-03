<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $asset->asset_tag }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .label { border: 1px solid #333; padding: 16px; width: 340px; text-align: center; }
        .barcode { height: 60px; }
        .qr { height: 120px; margin-bottom: 6px; }
        .tag { font-family: DejaVu Sans Mono, monospace; font-size: 18px; font-weight: bold; margin-top: 6px; letter-spacing: 1px; }
        .name { font-size: 12px; color: #333; margin-top: 2px; }
        .meta { font-size: 10px; color: #666; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="label">
        @if ($qr)
            <img class="qr" src="{{ $qr }}" alt="">
        @endif

        <img class="barcode" src="{{ $barcode }}" alt="">
        <div class="tag">{{ $asset->asset_tag }}</div>
        <div class="name">{{ $asset->name }}</div>
        <div class="meta">
            {{ $asset->category?->full_name }}
            @if ($asset->location) &middot; {{ $asset->location->name }} @endif
        </div>
    </div>
</body>
</html>
