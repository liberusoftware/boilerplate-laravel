@extends(theme_layout('app'))

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Theme System Demo
                </h1>
                <livewire:theme-switcher />
            </div>

            <div class="space-y-6">
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                        Current Theme Information
                    </h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium">Active Theme:</span>
                            <span class="ml-2 text-gray-600 dark:text-gray-400">{{ active_theme() }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Theme Label:</span>
                            <span class="ml-2 text-gray-600 dark:text-gray-400">{{ $themeConfig['label'] ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Description:</span>
                            <span class="ml-2 text-gray-600 dark:text-gray-400">{{ $themeConfig['description'] ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="font-medium">Version:</span>
                            <span class="ml-2 text-gray-600 dark:text-gray-400">{{ $themeConfig['version'] ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                        Theme Colors
                    </h2>
                    <div class="flex space-x-4">
                        @if(isset($themeConfig['colors']))
                            @foreach($themeConfig['colors'] as $colorName => $colorValue)
                                <div class="text-center">
                                    <div class="w-16 h-16 rounded-lg shadow-md mb-2 bg-{{ $colorValue }}-600"></div>
                                    <div class="text-xs font-medium">{{ ucfirst($colorName) }}</div>
                                    <div class="text-xs text-gray-500">{{ $colorValue }}</div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                        Available Themes
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach(theme()->getThemes() as $themeName => $themeData)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 {{ active_theme() === $themeName ? 'ring-2 ring-blue-500' : '' }}">
                                <h3 class="font-semibold text-lg">{{ $themeData['label'] ?? ucfirst($themeName) }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $themeData['description'] ?? 'No description' }}
                                </p>
                                @if(active_theme() === $themeName)
                                    <span class="inline-block mt-2 px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs rounded">
                                        Active
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">
                        Theme-Specific Buttons
                    </h2>
                    <div class="flex space-x-4">
                        <button class="theme-btn-primary">
                            Primary Button
                        </button>
                        <button class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                            Secondary Button
                        </button>
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700 dark:text-blue-200">
                                <strong>Tip:</strong> Use the theme switcher above to see how the page changes with different themes!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
