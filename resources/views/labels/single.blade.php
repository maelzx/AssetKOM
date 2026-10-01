<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $asset->asset_tag }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .label { border: 1px solid #333; padding: 16px; width: 320px; text-align: center; }
        .tag { font-size: 18px; font-weight: bold; margin-top: 8px; }
        .name { font-size: 12px; color: #333; }
        .meta { font-size: 10px; color: #666; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="label">
        <img src="{{ $qr }}" width="180" height="180" alt="">
        <div class="tag">{{ $asset->asset_tag }}</div>
        <div class="name">{{ $asset->name }}</div>
        <div class="meta">
            {{ $asset->category?->full_name }}
            @if ($asset->location) &middot; {{ $asset->location->name }} @endif
        </div>
    </div>
</body>
</html>
