@props(['title', 'subtitle' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-base-content sm:text-3xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1.5 max-w-2xl text-sm leading-6 text-base-content/60">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
