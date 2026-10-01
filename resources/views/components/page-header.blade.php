@props(['title', 'subtitle' => null])

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-base-content">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-base-content/60">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endif
</div>
