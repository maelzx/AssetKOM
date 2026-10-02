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
                    if (t === 'dark') t = 'assetkom-dark';
                    if (t === 'light') t = 'assetkom';
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
            // Bust cached pages/CSS after a new build when navigating via wire:navigate.
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
        <div class="flex min-h-screen flex-col">
            <livewire:layout.navigation />

            @if (isset($header))
                <header class="border-b border-base-300/70 bg-base-100/80 backdrop-blur">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="mt-8 border-t border-base-300/70 bg-base-100/40">
                <div class="mx-auto flex w-full max-w-7xl flex-col gap-1 px-4 py-5 text-xs text-base-content/50 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <span>{{ config('app.name', 'AssetKOM') }}</span>
                    <span>{{ __('Asset management workspace') }} · {{ now()->year }}</span>
                </div>
            </footer>
        </div>
    </body>
</html>
