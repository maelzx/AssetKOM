@props(['href' => null])

<a href="{{ $href ?? route('dashboard') }}" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <img src="{{ asset('images/brand/app-icon.png') }}" alt="" class="h-9 w-9 rounded-xl">
    <img src="{{ asset('images/brand/wordmark-light.png') }}" alt="AssetKOM" class="h-4 w-auto dark:hidden">
    <img src="{{ asset('images/brand/wordmark-dark.png') }}" alt="AssetKOM" class="hidden h-4 w-auto dark:block">
</a>
