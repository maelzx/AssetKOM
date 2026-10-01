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
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-base-200 font-sans text-base-content antialiased">
        <div class="flex min-h-screen flex-col">
            <livewire:layout.navigation />

            @if (isset($header))
                <header class="border-b border-base-300 bg-base-100">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="mx-auto w-full max-w-7xl px-4 py-8 text-xs text-base-content/50 sm:px-6 lg:px-8">
                {{ config('app.name', 'AssetKOM') }} · {{ now()->year }}
            </footer>
        </div>
    </body>
</html>
