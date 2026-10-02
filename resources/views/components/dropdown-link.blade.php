@props(['active' => false])

@php
$classes = ($active ?? false)
    ? 'block w-full rounded-lg px-4 py-2 text-start text-sm font-medium leading-5 bg-primary/10 text-primary'
    : 'block w-full rounded-lg px-4 py-2 text-start text-sm leading-5 text-base-content hover:bg-base-200 focus:outline-none transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active ?? false) aria-current="page" @endif>{{ $slot }}</a>
