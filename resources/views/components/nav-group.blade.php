@props(['label', 'active' => false])

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <button
        type="button"
        @click="open = ! open"
        :aria-expanded="open.toString()"
        aria-haspopup="true"
        class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-primary text-primary-content' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content' }}"
    >
        {{ $label }}
        <svg class="h-3.5 w-3.5 opacity-60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute left-0 z-50 mt-2 w-52 rounded-box bg-base-100 p-1 shadow-lg ring-1 ring-base-300"
        style="display: none;"
        @keydown.escape.window="open = false"
        @click="open = false"
    >
        {{ $slot }}
    </div>
</div>
