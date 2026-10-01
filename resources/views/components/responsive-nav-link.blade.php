@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg px-3 py-2 text-start text-base font-medium bg-primary text-primary-content'
            : 'block w-full rounded-lg px-3 py-2 text-start text-base font-medium text-base-content/80 hover:bg-base-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
