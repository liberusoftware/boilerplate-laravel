<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('app.name') . ' — Enterprise Laravel foundation' }}</title>
    <meta name="description" content="{{ $description ?? 'Auth, teams, roles, real-time chat, and multi-language — a production-ready Laravel + Filament foundation you can clone, brand, and ship.' }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite('resources/css/app.css')
</head>
<body class="antialiased bg-canvas text-ink-inverse" style="font-family: Inter, ui-sans-serif, system-ui, sans-serif;">
    <div class="relative flex flex-col sm:justify-center sm:items-center min-h-screen bg-canvas overflow-hidden">

        {{-- Atmospheric: a single low teal glow, top-center. Purposeful, not glassmorphism. --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 -top-40 h-80 bg-[radial-gradient(60%_100%_at_50%_0%,rgba(69,180,191,0.14),transparent_70%)]"></div>

        @if (filament()->hasLogin())
            <div class="sm:fixed sm:top-0 sm:right-0 p-4 sm:p-6 text-right z-10">
                @auth
                    <a href="{{ filament()->getHomeUrl() }}" class="font-semibold text-ink-muted-dark hover:text-ink-inverse transition focus-visible:outline focus-visible:outline-2 focus-visible:rounded-sm focus-visible:outline-teal-signal">Dashboard</a>
                @else
                    <a href="{{ filament()->getLoginUrl() }}" class="font-semibold text-ink-muted-dark hover:text-ink-inverse transition focus-visible:outline focus-visible:outline-2 focus-visible:rounded-sm focus-visible:outline-teal-signal">Log in</a>
                    @if (filament()->hasRegistration())
                        <a href="{{ filament()->getRegistrationUrl() }}" class="ml-2 sm:ml-4 font-semibold text-ink-muted-dark hover:text-ink-inverse transition focus-visible:outline focus-visible:outline-2 focus-visible:rounded-sm focus-visible:outline-teal-signal">Register</a>
                    @endif
                @endauth
            </div>
        @endif

        <main class="relative w-full flex-1 flex items-center justify-center">
            @yield('content')
        </main>

        @include('components.footer')
    </div>
</body>
</html>
