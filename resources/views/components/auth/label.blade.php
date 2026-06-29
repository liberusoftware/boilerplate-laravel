@props(['value' => null])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-ink-inverse mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
