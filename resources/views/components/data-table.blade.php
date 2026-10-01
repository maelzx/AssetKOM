@props(['paginator' => null])

<div class="card overflow-hidden border border-base-300 bg-base-100 shadow-sm">
    <div class="hidden overflow-x-auto md:block">
        {{ $table }}
    </div>

    <div class="md:hidden">
        {{ $cards }}
    </div>

    @if ($paginator && $paginator->hasPages())
        <div class="border-t border-base-300 px-4 py-3">
            {{ $paginator->links() }}
        </div>
    @endif
</div>
