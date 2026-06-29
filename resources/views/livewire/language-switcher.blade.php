<div class="relative inline-block text-left" x-data="{ open: false }">
    <div>
        <button type="button" @click="open = !open" 
                class="inline-flex items-center justify-center w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                id="language-menu" aria-haspopup="true">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
            </svg>
            {{ $availableLocales[$currentLocale] ?? 'English' }}
            <svg class="-mr-1 ml-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    <div x-show="open" 
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-50"
         role="menu" aria-orientation="vertical" aria-labelledby="language-menu">
        <div class="py-1" role="none">
            @foreach($availableLocales as $locale => $name)
                <button wire:click="switchLanguage('{{ $locale }}')"
                        class="w-full text-left px-4 py-2 text-sm {{ $currentLocale === $locale ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        role="menuitem">
                    <span class="flex items-center">
                        @if($currentLocale === $locale)
                            <svg class="w-4 h-4 mr-2 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        @else
                            <span class="w-4 h-4 mr-2"></span>
                        @endif
                        {{ $name }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</div>
