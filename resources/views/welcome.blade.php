<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Boilerplate Laravel') }} — a foundation you keep</title>
    <meta name="description" content="A Laravel + Filament + Livewire boilerplate that ships a credible, working app: auth, teams, roles, chat, themes and multi-language on a polished default.">

    @fonts

    <style>
        :root {
            --teal-primary: #1597a3;
            --teal-deep: #0e7f8a;
            --teal-emphasis: #0b6b74;
            --teal-on-dark: #45b4bf;
            --canvas: #fafcfc;
            --surface: #ffffff;
            --surface-sunken: #f1f5f5;
            --border: #dce4e4;
            --ink: #2a3338;
            --ink-muted: #626e74;
            --success: #1f9d57;

            --font-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            --font-mono: ui-monospace, 'JetBrains Mono', 'SF Mono', monospace;

            --r-sm: 6px;
            --r-md: 10px;
            --r-lg: 16px;
            --r-xl: 22px;
            --r-full: 999px;

            --shadow-sm: 0 1px 2px rgba(19, 42, 46, .06), 0 1px 1px rgba(19, 42, 46, .04);
            --shadow-md: 0 10px 30px -12px rgba(11, 55, 60, .22);
            --shadow-lg: 0 40px 80px -32px rgba(11, 55, 60, .30);

            --z-nav: 100;
            --maxw: 1120px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --canvas: #171c1f;
                --surface: #1f262a;
                --surface-sunken: #232c30;
                --border: #2d353a;
                --ink: #ecf0f0;
                --ink-muted: #a5afb3;
                --teal-emphasis: #45b4bf;
                --teal-deep: #45b4bf;
                --shadow-md: 0 10px 30px -12px rgba(0, 0, 0, .5);
                --shadow-lg: 0 40px 80px -30px rgba(0, 0, 0, .6);
                --shadow-sm: 0 1px 2px rgba(0, 0, 0, .4);
            }
        }

        *, *::before, *::after { box-sizing: border-box; }

        html { -webkit-text-size-adjust: 100%; scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }

        body {
            margin: 0;
            font-family: var(--font-sans);
            font-size: 0.9375rem;
            line-height: 1.6;
            color: var(--ink);
            background: var(--canvas);
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        a { color: var(--teal-emphasis); text-decoration: none; }
        a:hover { color: var(--teal-deep); }

        :focus-visible {
            outline: 2px solid var(--teal-primary);
            outline-offset: 2px;
            border-radius: var(--r-sm);
        }

        .wrap { width: 100%; max-width: var(--maxw); margin-inline: auto; padding-inline: clamp(1.25rem, 4vw, 2.5rem); }

        .skip { position: absolute; left: .75rem; top: -3rem; background: var(--surface); color: var(--ink); border: 1px solid var(--border); padding: .5rem .8rem; border-radius: var(--r-sm); z-index: 200; transition: top .15s ease; }
        .skip:focus { top: .75rem; color: var(--ink); }

        /* --- nav --- */
        .nav {
            position: sticky; top: 0; z-index: var(--z-nav);
            background: color-mix(in oklab, var(--canvas) 82%, transparent);
            backdrop-filter: saturate(180%) blur(12px);
            border-bottom: 1px solid transparent;
            transition: border-color .2s ease;
        }
        .nav[data-scrolled="true"] { border-bottom-color: var(--border); }
        .nav__inner { display: flex; align-items: center; justify-content: space-between; height: 64px; gap: 1rem; }
        .brand { display: inline-flex; align-items: center; gap: .6rem; font-weight: 600; color: var(--ink); letter-spacing: -0.01em; }
        .brand:hover { color: var(--ink); }
        .brand__mark { width: 26px; height: 26px; flex: none; }
        .nav__links { display: flex; align-items: center; gap: clamp(.75rem, 2vw, 1.6rem); }
        .nav__links a.muted { color: var(--ink-muted); font-weight: 500; }
        .nav__links a.muted:hover { color: var(--ink); }
        .nav__login { display: none; }
        @media (min-width: 720px) { .nav__login { display: inline; } }

        /* --- buttons --- */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            font: inherit; font-weight: 600; line-height: 1;
            padding: .7rem 1.15rem; border-radius: var(--r-md);
            border: 1px solid transparent; cursor: pointer; white-space: nowrap;
            transition: background-color .18s ease, border-color .18s ease, transform .18s cubic-bezier(.2,.7,.2,1), box-shadow .18s ease;
        }
        .btn--primary { background: var(--teal-emphasis); color: #fff; box-shadow: var(--shadow-sm); }
        .btn--primary:hover { background: var(--teal-deep); color: #fff; transform: translateY(-1px); box-shadow: var(--shadow-md); }
        @media (prefers-color-scheme: dark) { .btn--primary { color: #06232a; } .btn--primary:hover { color: #06232a; } }
        .btn--ghost { background: var(--surface); color: var(--ink); border-color: var(--border); }
        .btn--ghost:hover { background: var(--surface-sunken); color: var(--ink); border-color: color-mix(in oklab, var(--border) 60%, var(--ink)); }
        .btn--sm { padding: .5rem .85rem; font-size: .875rem; }

        /* --- hero --- */
        .hero { position: relative; overflow: hidden; padding-top: clamp(3rem, 8vw, 6rem); padding-bottom: clamp(3rem, 7vw, 5.5rem); }
        .hero__grid { display: grid; gap: clamp(2.5rem, 5vw, 4rem); grid-template-columns: 1fr; align-items: center; }
        @media (min-width: 940px) { .hero__grid { grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); } }

        .pill {
            display: inline-flex; align-items: center; gap: .5rem;
            font-size: .8125rem; font-weight: 500; color: var(--teal-emphasis);
            background: color-mix(in oklab, var(--teal-primary) 12%, var(--surface));
            border: 1px solid color-mix(in oklab, var(--teal-primary) 26%, transparent);
            padding: .32rem .7rem; border-radius: var(--r-full);
        }
        .pill__dot { width: 7px; height: 7px; border-radius: 50%; background: var(--teal-primary); animation: pulse 2.4s ease-out infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 color-mix(in oklab, var(--teal-primary) 55%, transparent); } 70% { box-shadow: 0 0 0 7px transparent; } 100% { box-shadow: 0 0 0 0 transparent; } }
        @media (prefers-reduced-motion: reduce) { .pill__dot { animation: none; } }

        .hero h1 {
            margin: 1.25rem 0 0;
            font-weight: 700; letter-spacing: -0.025em; line-height: 1.08;
            font-size: clamp(2.35rem, 5.4vw, 3.75rem);
            text-wrap: balance;
        }
        .hero h1 .sig { color: var(--teal-emphasis); white-space: nowrap; }
        .hero__lede { margin: 1.15rem 0 0; max-width: 36ch; font-size: clamp(1.02rem, 1.4vw, 1.175rem); line-height: 1.55; color: var(--ink-muted); text-wrap: pretty; }
        .hero__cta { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.75rem; }
        .hero__meta { margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 1.1rem; font-size: .8125rem; color: var(--ink-muted); }
        .hero__meta span { display: inline-flex; align-items: center; gap: .4rem; }
        .hero__meta svg { color: var(--teal-emphasis); flex: none; }

        /* signal line behind hero */
        .hero__signal { position: absolute; inset: 0; z-index: -1; overflow: hidden; pointer-events: none; }
        .hero__signal svg { position: absolute; right: -8%; top: 8%; width: 62%; height: auto; opacity: .5; color: var(--teal-primary); }
        @media (max-width: 939px) { .hero__signal svg { top: auto; bottom: -6%; right: -20%; width: 90%; opacity: .26; } }
        .hero__signal path { stroke-dasharray: 1600; stroke-dashoffset: 1600; animation: draw 2.8s cubic-bezier(.16,1,.3,1) .2s forwards; }
        @media (prefers-reduced-motion: reduce) { .hero__signal path { animation: none; stroke-dashoffset: 0; } }
        @keyframes draw { to { stroke-dashoffset: 0; } }

        /* --- app-shell mock --- */
        .mock {
            border-radius: var(--r-xl);
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            transform: perspective(1600px) rotateY(-6deg) rotateX(2deg);
            transform-origin: center left;
        }
        @media (max-width: 939px) { .mock { transform: none; } }
        .mock__bar { display: flex; align-items: center; gap: .5rem; padding: .7rem .9rem; border-bottom: 1px solid var(--border); background: var(--surface-sunken); }
        .mock__dots { display: flex; gap: .4rem; }
        .mock__dots i { width: 10px; height: 10px; border-radius: 50%; background: var(--border); }
        .mock__addr { flex: 1; text-align: center; font-family: var(--font-mono); font-size: .72rem; color: var(--ink-muted); }
        .mock__body { display: grid; grid-template-columns: 148px 1fr; min-height: 320px; }
        @media (max-width: 480px) { .mock__body { grid-template-columns: 56px 1fr; } }
        .mock__side { border-right: 1px solid var(--border); padding: .85rem .7rem; background: var(--surface); display: flex; flex-direction: column; gap: .2rem; }
        .mock__side .team { display: flex; align-items: center; gap: .5rem; padding: .35rem .45rem .8rem; font-weight: 600; font-size: .8rem; color: var(--ink); }
        .mock__side .team b { width: 22px; height: 22px; border-radius: 7px; background: var(--teal-emphasis); color: #fff; display: grid; place-items: center; font-size: .7rem; flex: none; }
        .navitem { display: flex; align-items: center; gap: .55rem; padding: .48rem .5rem; border-radius: var(--r-sm); font-size: .8rem; color: var(--ink-muted); }
        .navitem svg { flex: none; opacity: .85; }
        .navitem.is-active { background: color-mix(in oklab, var(--teal-primary) 13%, var(--surface)); color: var(--teal-emphasis); font-weight: 600; }
        .navitem .badge { margin-left: auto; font-size: .66rem; font-weight: 600; background: var(--teal-emphasis); color: #fff; border-radius: 999px; padding: .05rem .38rem; }
        @media (max-width: 480px) { .navitem span, .mock__side .team span { display: none; } .navitem { justify-content: center; } .navitem .badge { display: none; } }
        .mock__main { padding: 1rem 1.05rem; background: var(--canvas); display: flex; flex-direction: column; gap: .7rem; }
        .mock__main h4 { margin: .1rem 0 .3rem; font-size: .92rem; }
        .msg { max-width: 82%; padding: .55rem .75rem; border-radius: 13px; font-size: .8rem; line-height: 1.45; box-shadow: var(--shadow-sm); }
        .msg--in { align-self: flex-start; background: var(--surface); border: 1px solid var(--border); border-bottom-left-radius: 4px; }
        .msg--out { align-self: flex-end; background: var(--teal-emphasis); color: #fff; border-bottom-right-radius: 4px; }
        @media (prefers-color-scheme: dark) { .msg--out { color: #06232a; } }
        .msg small { display: block; margin-top: .2rem; font-size: .64rem; opacity: .72; }
        .composer { margin-top: auto; display: flex; align-items: center; gap: .5rem; padding: .5rem .6rem; background: var(--surface); border: 1px solid var(--border); border-radius: 999px; color: var(--ink-muted); font-size: .78rem; }
        .composer .send { margin-left: auto; width: 26px; height: 26px; border-radius: 50%; background: var(--teal-emphasis); color: #fff; display: grid; place-items: center; flex: none; }

        /* --- section scaffolding --- */
        section { padding-block: clamp(3.5rem, 8vw, 6rem); }
        .section-head { max-width: 42ch; }
        .section-head h2 { margin: 0; font-size: clamp(1.6rem, 3vw, 2.25rem); font-weight: 700; letter-spacing: -0.02em; line-height: 1.15; text-wrap: balance; }
        .section-head p { margin: .75rem 0 0; color: var(--ink-muted); font-size: 1.02rem; text-wrap: pretty; }

        /* features: shared border grid, varied widths — not identical cards */
        .features { margin-top: clamp(2rem, 4vw, 3rem); display: grid; gap: 1px; background: var(--border); border: 1px solid var(--border); border-radius: var(--r-lg); overflow: hidden; grid-template-columns: 1fr; }
        @media (min-width: 640px) { .features { grid-template-columns: 1fr 1fr; } }
        @media (min-width: 960px) { .features { grid-template-columns: repeat(3, 1fr); } }
        .feature { background: var(--surface); padding: clamp(1.4rem, 2.4vw, 1.85rem); display: flex; flex-direction: column; gap: .5rem; }
        .feature--wide { grid-column: 1 / -1; }
        @media (min-width: 960px) { .feature--wide { grid-column: span 2; } }
        .feature__ic { width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; background: color-mix(in oklab, var(--teal-primary) 13%, var(--surface)); color: var(--teal-emphasis); }
        .feature h3 { margin: .35rem 0 0; font-size: 1.02rem; font-weight: 600; letter-spacing: -0.01em; }
        .feature p { margin: 0; color: var(--ink-muted); font-size: .9rem; line-height: 1.55; }
        .feature code { font-family: var(--font-mono); font-size: .78rem; color: var(--teal-emphasis); background: color-mix(in oklab, var(--teal-primary) 10%, var(--surface)); padding: .05rem .35rem; border-radius: 5px; }

        /* theming showcase */
        .theming { background: var(--surface-sunken); border-block: 1px solid var(--border); }
        .theming__grid { display: grid; gap: clamp(2rem, 4vw, 3.5rem); grid-template-columns: 1fr; align-items: center; }
        @media (min-width: 900px) { .theming__grid { grid-template-columns: 1fr 1fr; } }
        .swatches { display: flex; gap: .6rem; flex-wrap: wrap; margin-top: 1.4rem; }
        .swatch { border-radius: var(--r-md); border: 1px solid var(--border); overflow: hidden; width: 92px; background: var(--surface); box-shadow: var(--shadow-sm); }
        .swatch .chip { height: 52px; }
        .swatch .lab { padding: .4rem .5rem; font-size: .68rem; color: var(--ink-muted); font-family: var(--font-mono); }
        .specimen { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-lg); padding: clamp(1.4rem, 3vw, 2rem); box-shadow: var(--shadow-md); }
        .specimen .big { font-size: clamp(2rem, 4vw, 2.75rem); font-weight: 700; letter-spacing: -0.03em; line-height: 1.1; }
        .specimen .row { display: flex; align-items: baseline; gap: .8rem; padding: .55rem 0; border-top: 1px solid var(--border); }
        .specimen .row:first-of-type { border-top: 0; }
        .specimen .row .k { font-size: .72rem; color: var(--ink-muted); font-family: var(--font-mono); width: 4.5rem; flex: none; }

        /* CTA band */
        .cta-band { text-align: center; }
        .cta-band .box { background: var(--teal-emphasis); border-radius: var(--r-xl); padding: clamp(2.5rem, 6vw, 4rem) clamp(1.5rem, 4vw, 3rem); color: #fff; position: relative; overflow: hidden; box-shadow: var(--shadow-lg); }
        .cta-band .box h2 { color: #fff; font-size: clamp(1.7rem, 3.4vw, 2.4rem); letter-spacing: -0.02em; margin: 0; text-wrap: balance; }
        .cta-band .box p { color: color-mix(in oklab, #fff 84%, var(--teal-primary)); margin: .7rem auto 0; max-width: 46ch; }
        .cta-band .btn--primary { background: #fff; color: var(--teal-emphasis); margin-top: 1.6rem; }
        .cta-band .btn--primary:hover { background: var(--surface-sunken); color: var(--teal-deep); }
        .cta-band .glow { position: absolute; width: 460px; height: 460px; border-radius: 50%; background: radial-gradient(circle, color-mix(in oklab, var(--teal-on-dark) 55%, transparent), transparent 70%); top: -40%; left: -8%; pointer-events: none; }

        /* footer */
        footer { border-top: 1px solid var(--border); padding-block: 2.5rem; color: var(--ink-muted); font-size: .85rem; }
        .foot { display: flex; flex-wrap: wrap; gap: 1rem 2rem; align-items: center; justify-content: space-between; }
        .foot__links { display: flex; flex-wrap: wrap; gap: 1.2rem; }
        .foot__links a { color: var(--ink-muted); }
        .foot__links a:hover { color: var(--ink); }

        /* load reveal — enhances already-visible content */
        .reveal { opacity: 0; transform: translateY(14px); }
        .is-ready .reveal { opacity: 1; transform: none; transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
        .is-ready .reveal[data-d="1"] { transition-delay: .06s; }
        .is-ready .reveal[data-d="2"] { transition-delay: .12s; }
        .is-ready .reveal[data-d="3"] { transition-delay: .18s; }
        .is-ready .reveal[data-d="4"] { transition-delay: .26s; }
        @media (prefers-reduced-motion: reduce) {
            .reveal, .is-ready .reveal { opacity: 1; transform: none; transition: none; }
        }
        /* no-JS safety: never gate content on the script */
        .no-js .reveal { opacity: 1; transform: none; }
    </style>
</head>
<body class="no-js">
<script>document.body.classList.remove('no-js');</script>

<a href="#content" class="skip">Skip to content</a>

<header class="nav" id="nav">
    <div class="wrap nav__inner">
        <a href="/" class="brand" aria-label="{{ config('app.name', 'Boilerplate Laravel') }} home">
            <svg class="brand__mark" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                <rect width="32" height="32" rx="8" fill="#0b6b74"/>
                <path d="M6 20.5c2.2 0 2.2-9 4.4-9s2.2 9 4.4 9 2.2-13 4.4-13 2.2 13 4.4 13" stroke="#45b4bf" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>{{ config('app.name', 'Boilerplate Laravel') }}</span>
        </a>
        <nav class="nav__links" aria-label="Primary">
            <a href="#features" class="muted">Features</a>
            <a href="https://github.com/liberusoftware/boilerplate-laravel" class="muted" rel="noopener">GitHub</a>
            @if (Route::has('login'))
                <a href="{{ route('login') }}" class="muted nav__login">Log in</a>
            @endif
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="btn btn--primary btn--sm">Get started</a>
            @endif
        </nav>
    </div>
</header>

<main id="content">
    <section class="hero">
        <div class="hero__signal" aria-hidden="true">
            <svg viewBox="0 0 800 400" fill="none" preserveAspectRatio="xMidYMid meet">
                <path d="M0 220 C 120 220 120 60 200 60 S 280 340 360 340 400 120 440 120 480 260 520 260 560 90 620 90 720 220 800 220"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="wrap hero__grid">
            <div>
                <span class="pill reveal" data-d="1"><span class="pill__dot"></span> Laravel 13 · Filament 5 · Livewire</span>
                <h1 class="reveal" data-d="2">A foundation you <span class="sig">keep</span>, not scaffold you rip out.</h1>
                <p class="hero__lede reveal" data-d="3">Auth, teams, roles, real-time chat, themes and multi-language — working out of the box on a clean, considered default. Clone it and build your app, not your plumbing.</p>
                <div class="hero__cta reveal" data-d="3">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn--primary">Get started free</a>
                    @endif
                    <a href="https://github.com/liberusoftware/boilerplate-laravel" class="btn btn--ghost" rel="noopener">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.58 2 12.25c0 4.54 2.87 8.39 6.84 9.75.5.09.68-.22.68-.49 0-.24-.01-.87-.01-1.71-2.78.62-3.37-1.37-3.37-1.37-.45-1.18-1.11-1.49-1.11-1.49-.91-.64.07-.62.07-.62 1 .07 1.53 1.06 1.53 1.06.9 1.57 2.36 1.12 2.94.85.09-.66.35-1.12.63-1.38-2.22-.26-4.55-1.14-4.55-5.05 0-1.12.39-2.03 1.03-2.75-.1-.26-.45-1.3.1-2.71 0 0 .84-.28 2.75 1.05a9.34 9.34 0 0 1 5 0c1.91-1.33 2.75-1.05 2.75-1.05.55 1.41.2 2.45.1 2.71.64.72 1.03 1.63 1.03 2.75 0 3.92-2.34 4.79-4.57 5.04.36.32.68.94.68 1.9 0 1.37-.01 2.48-.01 2.82 0 .27.18.59.69.49A10.02 10.02 0 0 0 22 12.25C22 6.58 17.52 2 12 2Z"/></svg>
                        View on GitHub
                    </a>
                </div>
                <div class="hero__meta reveal" data-d="4">
                    <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> MIT licensed</span>
                    <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Docker-first dev stack</span>
                    <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Tested, WCAG AA</span>
                </div>
            </div>

            <div class="reveal" data-d="4">
                <div class="mock" role="img" aria-label="The boilerplate's team messaging screen in the Clear Signal teal theme: a sidebar with Dashboard, Teams, Messages and Settings, beside a team conversation.">
                    <div class="mock__bar">
                        <div class="mock__dots" aria-hidden="true"><i></i><i></i><i></i></div>
                        <div class="mock__addr">app · messages</div>
                    </div>
                    <div class="mock__body">
                        <aside class="mock__side" aria-hidden="true">
                            <div class="team"><b>AC</b><span>Acme Co.</span></div>
                            <div class="navitem"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg><span>Dashboard</span></div>
                            <div class="navitem"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg><span>Teams</span></div>
                            <div class="navitem is-active"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span>Messages</span><span class="badge">3</span></div>
                            <div class="navitem"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-2.82 1.17V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 8 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15H4.5a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 6 9.4l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 11 4.6V4.5a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 2.72 1.06l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 11h.1a2 2 0 0 1 0 4z"/></svg><span>Settings</span></div>
                        </aside>
                        <div class="mock__main" aria-hidden="true">
                            <h4>Design team</h4>
                            <div class="msg msg--in">Pushed the theme picker to staging — panels pick up teal now. <small>Priya · 9:41</small></div>
                            <div class="msg msg--out">Nice. Shipping Clear Signal as the default? <small>You · 9:42</small></div>
                            <div class="msg msg--in">Keeping stock default; teal is opt-in per tenant. <small>Priya · 9:42</small></div>
                            <div class="composer"><span>Message the team…</span><span class="send"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features">
        <div class="wrap">
            <div class="section-head">
                <h2>Everything a real app needs, already wired.</h2>
                <p>Not a landing-page demo — the layered stack your app actually ships on, integrated and tested.</p>
            </div>
            <div class="features">
                <div class="feature feature--wide">
                    <div class="feature__ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                    <h3>Teams, auth &amp; OAuth from day one</h3>
                    <p>Fortify + Jetstream give you registration, profiles, 2FA and passkeys; Socialstream adds GitHub, Google, Facebook and Twitter sign-in. Every user lands on a team, scoped and ready.</p>
                </div>
                <div class="feature">
                    <div class="feature__ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                    <h3>Roles &amp; permissions</h3>
                    <p>Team-scoped Spatie roles with a Filament Shield policy generated for every resource. Multi-tenant by construction.</p>
                </div>
                <div class="feature">
                    <div class="feature__ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
                    <h3>Real-time chat &amp; messaging</h3>
                    <p>Reverb WebSockets over Laravel Echo, with encrypted private messages and group chat already modelled and tested.</p>
                </div>
                <div class="feature">
                    <div class="feature__ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="6.5" cy="12" r="2.5"/><circle cx="17.5" cy="14.5" r="2.5"/><path d="M8.7 10.8 15 8M9 13.5l6 1.5"/></svg></div>
                    <h3>Themes you control</h3>
                    <p>A theme system with an admin-selectable, site-wide look. <code>clear-signal</code> ships as a ready teal option; add your own as a Tailwind bundle.</p>
                </div>
                <div class="feature">
                    <div class="feature__ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 0 20 15.3 15.3 0 0 1 0-20z"/></svg></div>
                    <h3>Multi-language</h3>
                    <p>Per-user locale resolved on every request across the app and both Filament panels, with a switcher and on-demand translation.</p>
                </div>
                <div class="feature feature--wide">
                    <div class="feature__ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 18 22 12 16 6M8 6 2 12l6 6"/></svg></div>
                    <h3>Modules, queues &amp; a Docker-first stack</h3>
                    <p>An install / enable / disable module system, Horizon-managed Redis queues, and Octane + RoadRunner for production — all running locally via <code>docker compose up</code>. Pest and PHPStan level 5 keep it honest.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="theming">
        <div class="wrap theming__grid">
            <div>
                <div class="section-head">
                    <h2>Distinctly un-stock, by construction.</h2>
                    <p>The boilerplate ships its own design system — “Clear Signal”: a teal signal on a quiet neutral workbench. Switch it site-wide from the admin panel; the interface recedes so the task stays in focus.</p>
                </div>
                <div class="swatches" aria-hidden="true">
                    <div class="swatch"><div class="chip" style="background:#0b6b74"></div><div class="lab">teal / ink</div></div>
                    <div class="swatch"><div class="chip" style="background:#1597a3"></div><div class="lab">teal / 500</div></div>
                    <div class="swatch"><div class="chip" style="background:#f1f5f5"></div><div class="lab">surface</div></div>
                    <div class="swatch"><div class="chip" style="background:#2a3338"></div><div class="lab">ink</div></div>
                </div>
            </div>
            <div class="specimen">
                <div class="big" style="color:var(--teal-emphasis)">Clear Signal</div>
                <p style="margin:.4rem 0 1.1rem;color:var(--ink-muted)">Inter · fixed rem scale · flat at rest</p>
                <div class="row"><span class="k">Display</span><span style="font-weight:700;letter-spacing:-.02em">Keep it, don’t rip it out</span></div>
                <div class="row"><span class="k">Body</span><span style="color:var(--ink)">Calm, professional, low ceremony.</span></div>
                <div class="row"><span class="k">Accent</span><span><a href="#features">a considered teal link →</a></span></div>
            </div>
        </div>
    </section>

    <section class="cta-band">
        <div class="wrap">
            <div class="box">
                <div class="glow" aria-hidden="true"></div>
                <h2>Start on a foundation, not a blank file.</h2>
                <p>Clone it, run <code style="font-family:var(--font-mono);color:#fff">docker compose up</code>, and you’re looking at a working, themed app in minutes.</p>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn--primary">Create your account</a>
                @endif
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="wrap foot">
        <span>© {{ date('Y') }} {{ config('app.name', 'Boilerplate Laravel') }} · MIT licensed</span>
        <nav class="foot__links" aria-label="Footer">
            <a href="https://github.com/liberusoftware/boilerplate-laravel" rel="noopener">GitHub</a>
            <a href="#features">Features</a>
            @if (Route::has('login'))<a href="{{ route('login') }}">Log in</a>@endif
            @if (Route::has('register'))<a href="{{ route('register') }}">Get started</a>@endif
        </nav>
    </div>
</footer>

<script>
    (function () {
        requestAnimationFrame(function () { document.body.classList.add('is-ready'); });
        var nav = document.getElementById('nav');
        var onScroll = function () { nav.setAttribute('data-scrolled', window.scrollY > 8 ? 'true' : 'false'); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    })();
</script>
</body>
</html>
