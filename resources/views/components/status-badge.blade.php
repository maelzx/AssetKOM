@props(['status'])

@php
    $status = $status instanceof \App\Enums\AssetStatus ? $status : \App\Enums\AssetStatus::from($status);
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $status->color() }}">
    {{ $status->label() }}
</span>
