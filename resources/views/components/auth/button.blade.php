@props([])

<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex w-full items-center justify-center rounded-lg bg-teal-signal px-5 py-2.5 text-sm font-semibold text-canvas shadow-sm transition hover:bg-[#5cc2cd] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-inverse disabled:opacity-50']) }}>
    {{ $slot }}
</button>
