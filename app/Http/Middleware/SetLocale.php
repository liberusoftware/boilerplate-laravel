<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority: 1. Request parameter, 2. Session, 3. User preference, 4. Browser, 5. Default
        
        $locale = null;

        // 1. Check if locale is set in request
        if ($request->has('locale')) {
            $locale = $request->get('locale');
            Session::put('locale', $locale);
        }
        
        // 2. Check session
        if (!$locale && Session::has('locale')) {
            $locale = Session::get('locale');
        }
        
        // 3. Check authenticated user's preference
        if (!$locale && auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
        }
        
        // 4. Try to detect from browser
        if (!$locale) {
            $locale = $this->detectLocaleFromBrowser($request);
        }
        
        // 5. Fallback to default
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }

        // Validate and set locale
        $supportedLocales = config('app.supported_locales', ['en', 'es', 'fr', 'de']);
        
        if (in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Detect locale from browser's Accept-Language header
     *
     * @param Request $request
     * @return string|null
     */
    private function detectLocaleFromBrowser(Request $request): ?string
    {
        $acceptLanguage = $request->server('HTTP_ACCEPT_LANGUAGE');
        
        if (!$acceptLanguage) {
            return null;
        }

        $supportedLocales = config('app.supported_locales', ['en', 'es', 'fr', 'de']);
        
        // Parse Accept-Language header
        $languages = [];
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $acceptLanguage, $matches);
        
        if (count($matches[1])) {
            $languages = array_combine($matches[1], $matches[4]);
            
            foreach ($languages as $lang => $val) {
                if ($val === '') {
                    $languages[$lang] = 1;
                }
            }
            
            arsort($languages, SORT_NUMERIC);
            
            foreach ($languages as $lang => $priority) {
                $lang = substr($lang, 0, 2); // Get first two characters
                
                if (in_array($lang, $supportedLocales)) {
                    return $lang;
                }
            }
        }
        
        return null;
    }
}
