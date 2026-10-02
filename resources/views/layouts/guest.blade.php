<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AssetKOM') }}</title>
        <script>
            (function () {
                try {
                    var t = localStorage.getItem('theme');
                    if (t) document.documentElement.setAttribute('data-theme', t);
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600;700&family=Fira+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        <meta name="asset-version" content="{{ \Illuminate\Support\Facades\Vite::asset('resources/css/app.css') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            (function () {
                function checkAssetVersion() {
                    var meta = document.querySelector('meta[name="asset-version"]');
                    if (!meta) return;
                    var current = meta.getAttribute('content');
                    var stored = null;
                    try { stored = localStorage.getItem('asset-version'); } catch (e) {}
                    if (stored && stored !== current) {
                        try { localStorage.setItem('asset-version', current); } catch (e) {}
                        window.location.reload();
                        return;
                    }
                    try { localStorage.setItem('asset-version', current); } catch (e) {}
                }
                document.addEventListener('DOMContentLoaded', checkAssetVersion);
                document.addEventListener('livewire:navigated', checkAssetVersion);
            })();
        </script>
    </head>
    <body class="min-h-screen bg-base-200 font-sans text-base-content antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <x-brand :href="url('/')" class="mb-6" />

            <div class="w-full max-w-md rounded-2xl bg-base-100 p-6 shadow-xl ring-1 ring-base-300 sm:p-8">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-base-content/50">
                {{ config('app.name', 'AssetKOM') }} · {{ now()->year }}
            </p>
        </div>
    </body>
</html>
