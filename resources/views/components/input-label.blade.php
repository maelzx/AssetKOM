@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-1 block text-sm font-medium text-base-content']) }}>
    {{ $value ?? $slot }}
</label>
