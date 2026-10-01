<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Asset labels') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        td { width: 33.33%; padding: 8px; text-align: center; border: 1px dashed #999; }
        .tag { font-size: 12px; font-weight: bold; margin-top: 4px; }
        .name { font-size: 9px; color: #444; }
    </style>
</head>
<body>
    <table>
        @foreach ($labels->chunk(3) as $chunk)
            <tr>
                @foreach ($chunk as $label)
                    <td>
                        <img src="{{ $label['qr'] }}" width="110" height="110" alt="">
                        <div class="tag">{{ $label['asset']->asset_tag }}</div>
                        <div class="name">{{ $label['asset']->name }}</div>
                    </td>
                @endforeach
                @for ($i = $chunk->count(); $i < 3; $i++)
                    <td></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>
