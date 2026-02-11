<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Liberu</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        @vite('resources/css/app.css')
    </head>
    <body class="antialiased bg-neutral-900">
        <div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-linear-to-br dark:from-neutral-900 dark:via-neutral-800 dark:to-neutral-900 selection:bg-red-500 selection:text-white">
            @if (filament()->hasLogin())
                <div class="sm:fixed sm:top-0 sm:right-0 p-6 text-right z-10">
                    @auth
                        <a href="{{ filament()->getHomeUrl() }}" class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Dashboard</a>
                    @else
                        <a href="{{ filament()->getLoginUrl() }}" class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Log in</a>

                        @if (filament()->hasRegistration())
                            <a href="{{ filament()->getRegistrationUrl() }}" class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Register</a>
                        @endif
                    @endauth
                </div>
            @endif

            <div class="max-w-7xl mx-auto p-6 lg:p-8">
                <div class="flex justify-center">
                    <h1 class="text-6xl md:text-8xl font-bold text-white mb-10 leading-tight text-center">
                        <span class="text-[#ff0] drop-shadow-lg">Liberu</span>
                        <br>
                        <span class="text-4xl md:text-6xl text-neutral-300 font-light">Enterprise Laravel Platform</span>
                    </h1>

                </div>
                <div class="flex justify-center mt-6">
                    <a
                        href="https://liberu.co.uk"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-2 text-neutral-400 hover:text-white transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 21a9 9 0 100-18 9 9 0 000 18zM3.6 9h16.8M3.6 15h16.8M12 3c2.5 3 2.5 15 0 18" />
                        </svg>
                        <span class="text-sm">liberu.co.uk</span>
                    </a>
                </div>
                    

            </div>
        </div>
    </body>
</html>
