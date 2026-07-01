@props(['for'])

@error($for)
    <p {{ $attributes->merge(['class' => 'cs-error']) }}>{{ $message }}</p>
@enderror
