@extends('layouts.guest')

@section('content')
<style>
    /* Content is visible by default; motion only enhances it. Never gate visibility
       on an animation — a non-animating context would ship the section blank. */
    @media (prefers-reduced-motion: no-preference) {
        @keyframes signal-rise {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .signal-rise { animation: signal-rise .6s cubic-bezier(0.22, 1, 0.36, 1) both; }
        .signal-rise-2 { animation-delay: .08s; }
        .signal-rise-3 { animation-delay: .16s; }
        .signal-rise-4 { animation-delay: .24s; }
    }
</style>

<section class="w-full max-w-3xl mx-auto px-6 py-20 text-center">
    <p class="signal-rise text-sm font-semibold tracking-wide text-ink-muted-dark">
        {{ config('app.name') }}
    </p>

    <h1 class="signal-rise signal-rise-2 mt-5 text-4xl sm:text-5xl md:text-6xl font-bold leading-tight tracking-tight text-ink-inverse text-balance">
        The enterprise Laravel foundation<br class="hidden sm:block">
        you <span class="text-teal-signal">ship on</span>.
    </h1>

    <p class="signal-rise signal-rise-3 mx-auto mt-6 max-w-xl text-lg leading-relaxed text-ink-muted-dark text-pretty">
        Auth, teams, roles, real-time chat, and multi-language — production-ready
        from the first commit. Clone it, brand it, deploy.
    </p>

    @if (filament()->hasLogin())
        <div class="signal-rise signal-rise-3 mt-10 flex flex-wrap items-center justify-center gap-3">
            @auth
                <a href="{{ filament()->getHomeUrl() }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-signal px-5 py-3 text-sm font-semibold text-canvas shadow-sm transition hover:bg-[#5cc2cd] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-inverse">
                    Go to dashboard
                </a>
            @else
                @if (filament()->hasRegistration())
                    <a href="{{ filament()->getRegistrationUrl() }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-signal px-5 py-3 text-sm font-semibold text-canvas shadow-sm transition hover:bg-[#5cc2cd] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-inverse">
                        Get started
                    </a>
                @endif
                <a href="{{ filament()->getLoginUrl() }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg border border-border-dark bg-transparent px-5 py-3 text-sm font-semibold text-ink-inverse transition hover:bg-surface-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-signal">
                    Log in
                </a>
            @endauth
        </div>
    @endif

    <p class="signal-rise signal-rise-4 mt-12 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm text-ink-muted-dark">
        <span>Teams &amp; roles</span>
        <span aria-hidden="true" class="text-border-dark">&middot;</span>
        <span>Real-time chat</span>
        <span aria-hidden="true" class="text-border-dark">&middot;</span>
        <span>Passkey auth</span>
        <span aria-hidden="true" class="text-border-dark">&middot;</span>
        <span>Multi-language</span>
    </p>

    <p class="signal-rise signal-rise-4 mt-10 text-xs text-ink-muted-dark/70">
        Powered by
        <a href="https://liberu.co.uk" target="_blank" rel="noopener noreferrer"
           class="font-medium text-ink-muted-dark underline-offset-4 hover:text-ink-inverse hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:rounded-sm focus-visible:outline-teal-signal">Liberu</a>
    </p>
</section>
@endsection
