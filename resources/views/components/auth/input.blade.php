@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'block w-full rounded-lg border border-border-dark bg-canvas px-3.5 py-2.5 text-sm text-ink-inverse placeholder:text-ink-muted-dark transition focus:border-teal-signal focus:outline-none focus:ring-2 focus:ring-teal-signal/40 disabled:opacity-50']) !!}>
