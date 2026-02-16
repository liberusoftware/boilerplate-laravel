<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Multi-Language Support</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                This application supports multiple languages with automated translations. 
                Use the "Generate Translations" button above to create translations for all supported languages.
            </p>

            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-3">Supported Languages</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($supportedLanguages as $code => $name)
                        <div class="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <svg class="w-8 h-8 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                            <div>
                                <div class="font-semibold">{{ $name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ strtoupper($code) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Translation Features</h4>
                <ul class="list-disc list-inside text-sm text-blue-800 dark:text-blue-200 space-y-1">
                    <li>Automated translation using MyMemory Translation API</li>
                    <li>Language detection from browser preferences</li>
                    <li>User-specific language preferences</li>
                    <li>Session-based language switching</li>
                    <li>Cached translations for better performance</li>
                </ul>
            </div>

            <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">Usage Instructions</h4>
                <ol class="list-decimal list-inside text-sm text-yellow-800 dark:text-yellow-200 space-y-1">
                    <li>Click "Generate Translations" to create translations for all languages</li>
                    <li>Select source language (default: English)</li>
                    <li>Optionally select a specific target language or leave empty for all</li>
                    <li>Enable "Overwrite" to update existing translations</li>
                    <li>Users can switch languages using the language switcher in the navigation</li>
                </ol>
            </div>

            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <h4 class="font-semibold mb-2">Command Line Usage</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    You can also generate translations using the Artisan command:
                </p>
                <code class="block p-3 bg-gray-900 text-green-400 rounded text-sm overflow-x-auto">
                    php artisan translate:generate --source=en --target=es --force
                </code>
            </div>
        </div>
    </div>
</x-filament-panels::page>
