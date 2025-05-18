@props([
    'user' => null,
    'role' => null,
    'dashboardUrl' => null,
])
@if (auth()->check())
    @php
        $user = auth()->user();
        $role = $user->getRoleNames()->first() ?? 'user';
        $dashboardUrl = $role === 'admin' ? '/admin' : '/' . $role;
    @endphp
@endif
<nav class="bg-white border-gray-200 dark:bg-gray-900">
    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
        <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
            <img src="{{ asset('build/images/logo.png') }}" class="h-8" alt="{{ config('app.name') }}" />
            <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">
                {{ Str::upper(config('app.name')) }}</span>
        </a>


        <div class="items-center hidden justify-between w-full  lg:flex lg:w-auto" id="navbar-cta">
            <ul
                class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                {!! app(App\Services\MenuService::class)->buildMenu() !!}
            </ul>
        </div>

        <div class="flex items-center space-x-3 rtl:space-x-reverse">
            @if (auth()->check())
                @php
                    $user = auth()->user();
                    $role = $user->getRoleNames()->first() ?? 'user';
                    $dashboardUrl = $role === 'admin' ? '/admin' : '/' . $role;
                @endphp

                <a href="{{ $dashboardUrl }}"
                    class="hover:text-blue-700 px-3 py-2 rounded-md text-sm font-medium lg:hidden">
                    {{ ucfirst($role) }} Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="hover:text-blue-700 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                <a href="{{ route('register') }}"
                    class="hover:text-blue-700 px-3 py-2 rounded-md text-sm font-medium ml-2">Register</a>
            @endif

            <button id="menuToggleButton" data-collapse-toggle="menuToggle" type="button"
                class="inline-flex items-center justify-center p-2 w-10 h-10 text-sm text-gray-500 rounded-lg lg:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                aria-controls="menuToggle" aria-expanded="false">
                <span class="sr-only">Open main menu</span>
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 17 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M1 1h15M1 7h15M1 13h15" />
                </svg>
            </button>
        </div>
        @if (auth()->check())
            <a href="{{ $dashboardUrl }}"
                class="hover:text-blue-700 px-3 py-2 rounded-md text-sm font-medium hidden lg:block">
                {{ ucfirst($role) }} Dashboard
            </a>
        @endif
    </div>

    <div class="hidden lg:hidden" id="menuToggle">
        <ul class="flex flex-col font-medium mt-4 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
            {!! app(App\Services\MenuService::class)->buildMenu() !!}
        </ul>
    </div>
</nav>

<script>
    document.getElementById('menuToggleButton').addEventListener('click', function() {
        var menuToggle = document.getElementById('menuToggle');
        menuToggle.classList.toggle('hidden');
    });
</script>
