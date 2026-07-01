@props(['value'])

<label {{ $attributes->merge(['class' => 'cs-label']) }}>
    {{ $value ?? $slot }}
</label>
