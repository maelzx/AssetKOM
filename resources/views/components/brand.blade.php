@props(['href' => null])

<a href="{{ $href ?? route('dashboard') }}" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <span class="grid h-9 w-9 place-items-center rounded-xl bg-primary text-lg font-black text-primary-content">A</span>
    <span class="text-lg font-bold tracking-tight text-base-content">Asset<span class="text-primary">KOM</span></span>
</a>
