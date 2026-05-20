<?php

use Illuminate\Support\Facades\App;

if (!function_exists('current_locale')) {
    /**
     * Get the current application locale
     *
     * @return string
     */
    function current_locale(): string
    {
        return App::getLocale();
    }
}

if (!function_exists('supported_locales')) {
    /**
     * Get all supported locales
     *
     * @return array
     */
    function supported_locales(): array
    {
        return config('app.supported_locales', ['en' => 'English']);
    }
}

if (!function_exists('is_locale_supported')) {
    /**
     * Check if a locale is supported
     *
     * @param string $locale
     * @return bool
     */
    function is_locale_supported(string $locale): bool
    {
        return array_key_exists($locale, supported_locales());
    }
}

if (!function_exists('get_locale_name')) {
    /**
     * Get the name of a locale
     *
     * @param string|null $locale
     * @return string
     */
    function get_locale_name(?string $locale = null): string
    {
        $locale = $locale ?? current_locale();
        $locales = supported_locales();
        
        return $locales[$locale] ?? $locale;
    }
}

if (!function_exists('switch_locale')) {
    /**
     * Switch the application locale
     *
     * @param string $locale
     * @return bool
     */
    function switch_locale(string $locale): bool
    {
        if (!is_locale_supported($locale)) {
            return false;
        }
        
        App::setLocale($locale);
        session(['locale' => $locale]);
        
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
        
        return true;
    }
}

if (!function_exists('translate_text')) {
    /**
     * Translate text to a target language
     *
     * @param string $text
     * @param string $targetLang
     * @param string $sourceLang
     * @return string
     */
    function translate_text(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        $service = app(\App\Services\TranslationService::class);
        return $service->translate($text, $targetLang, $sourceLang);
    }
}

if (!function_exists('locale_route')) {
    /**
     * Generate a URL with locale parameter
     *
     * @param string $name
     * @param array $parameters
     * @param string|null $locale
     * @return string
     */
    function locale_route(string $name, array $parameters = [], ?string $locale = null): string
    {
        $locale = $locale ?? current_locale();
        $parameters['locale'] = $locale;
        
        return route($name, $parameters);
    }
}

if (!function_exists('trans_fallback')) {
    /**
     * Translate with fallback to key
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    function trans_fallback(string $key, array $replace = [], ?string $locale = null): string
    {
        $translation = __($key, $replace, $locale);
        
        // If translation returns the key, it means translation doesn't exist
        if ($translation === $key) {
            // Extract the last part of the key as a fallback
            $parts = explode('.', $key);
            return ucfirst(str_replace('_', ' ', end($parts)));
        }
        
        return $translation;
    }
}

if (!function_exists('locale_flag')) {
    /**
     * Get emoji flag for a locale
     *
     * @param string|null $locale
     * @return string
     */
    function locale_flag(?string $locale = null): string
    {
        $locale = $locale ?? current_locale();
        
        $flags = [
            'en' => '🇬🇧',
            'es' => '🇪🇸',
            'fr' => '🇫🇷',
            'de' => '🇩🇪',
            'it' => '🇮🇹',
            'pt' => '🇵🇹',
            'ru' => '🇷🇺',
            'ja' => '🇯🇵',
            'zh' => '🇨🇳',
            'ar' => '🇸🇦',
        ];
        
        return $flags[$locale] ?? '🌐';
    }
}

if (!function_exists('locale_direction')) {
    /**
     * Get text direction for a locale (ltr or rtl)
     *
     * @param string|null $locale
     * @return string
     */
    function locale_direction(?string $locale = null): string
    {
        $locale = $locale ?? current_locale();
        
        $rtlLocales = ['ar', 'he', 'fa', 'ur'];
        
        return in_array($locale, $rtlLocales) ? 'rtl' : 'ltr';
    }
}
