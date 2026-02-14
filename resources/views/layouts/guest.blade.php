<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Liberu' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    @vite('resources/css/app.css')
</head>
<body class="antialiased bg-neutral-900">
    <div class="relative flex flex-col sm:justify-center sm:items-center min-h-screen
        bg-dots-darker bg-center bg-gray-100
        dark:bg-linear-to-br dark:from-neutral-900 dark:via-neutral-800 dark:to-neutral-900">

        @if (filament()->hasLogin())
            <div class="sm:fixed sm:top-0 sm:right-0 p-4 sm:p-6 text-right z-10">
                @auth
                    <a href="{{ filament()->getHomeUrl() }}" class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Dashboard</a>
                @else
                    <a href="{{ filament()->getLoginUrl() }}" class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Log in</a>
                    @if (filament()->hasRegistration())
                        <a href="{{ filament()->getRegistrationUrl() }}" class="ml-2 sm:ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Register</a>
                    @endif
                @endauth
            </div>
        @endif

        <main class="w-full flex-1 flex items-center justify-center">
            @yield('content')
        </main>

        @include('components.footer')
    </div>
</body>
</html>
