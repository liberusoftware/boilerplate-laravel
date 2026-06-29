@props(['title' => null, 'subtitle' => null])

<div class="w-full max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="rounded-xl border border-border-dark bg-surface-dark p-6 sm:p-8">
        <a href="{{ url('/') }}" class="inline-block text-sm font-semibold tracking-wide text-ink-muted-dark transition hover:text-ink-inverse focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-teal-signal">
            {{ config('app.name') }}
        </a>

        @if ($title)
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-ink-inverse text-balance">{{ $title }}</h1>
        @endif

        @if ($subtitle)
            <p class="mt-2 text-sm leading-relaxed text-ink-muted-dark text-pretty">{{ $subtitle }}</p>
        @endif

        <div class="mt-6">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="mt-6 border-t border-border-dark pt-5 text-center text-sm text-ink-muted-dark">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
