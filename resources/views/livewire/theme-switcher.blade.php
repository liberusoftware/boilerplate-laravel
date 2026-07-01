<div class="theme-switcher">
    @if (session()->has('message'))
        <div class="alert alert-success mb-2 text-green-600">
            {{ session('message') }}
        </div>
    @endif
    
    <div class="relative inline-block text-left">
        <div>
            <button type="button" 
                    class="inline-flex justify-center w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none"
                    id="theme-menu-button" 
                    aria-expanded="true" 
                    aria-haspopup="true"
                    onclick="document.getElementById('theme-menu').classList.toggle('hidden')">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
                Theme: {{ ucfirst($currentTheme) }}
                <svg class="-mr-1 ml-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>

        <div class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 dark:divide-gray-700 focus:outline-none z-50" 
             id="theme-menu" 
             role="menu" 
             aria-orientation="vertical" 
             aria-labelledby="theme-menu-button">
            <div class="py-1" role="none">
                @foreach($availableThemes as $themeKey => $themeData)
                    <button wire:click="switchTheme('{{ $themeKey }}')" 
                            class="w-full text-left px-4 py-2 text-sm {{ $currentTheme === $themeKey ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white font-semibold' : 'text-gray-700 dark:text-gray-200' }} hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"
                            role="menuitem">
                        <div class="flex items-center">
                            @if($currentTheme === $themeKey)
                                <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <span class="w-4 h-4 mr-2"></span>
                            @endif
                            <div>
                                <div class="font-medium">{{ $themeData['label'] ?? ucfirst($themeKey) }}</div>
                                @if(isset($themeData['description']))
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $themeData['description'] }}</div>
                                @endif
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('theme-menu');
        const button = document.getElementById('theme-menu-button');
        
        if (menu && button && !menu.contains(event.target) && !button.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });
</script>
