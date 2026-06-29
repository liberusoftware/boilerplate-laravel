<?php

namespace App\Http\Middleware;

use App\Models\User;
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
     * Priority: request param → session → authenticated user → browser → default.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        if ($request->has('locale')) {
            $locale = $request->string('locale')->value();
            Session::put('locale', $locale);
        }

        if (! $locale) {
            $session = Session::get('locale');
            $locale = is_string($session) ? $session : null;
        }

        $user = auth()->user();
        if (! $locale && $user instanceof User && is_string($user->locale) && $user->locale !== '') {
            $locale = $user->locale;
        }

        if (! $locale) {
            $locale = $this->detectLocaleFromBrowser($request);
        }

        if (! $locale) {
            $default = config('app.locale', 'en');
            $locale = is_string($default) ? $default : 'en';
        }

        $supportedLocales = array_keys((array) config('app.supported_locales', ['en' => 'English']));

        if (in_array($locale, $supportedLocales, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Detect the best supported locale from the browser's Accept-Language header.
     */
    private function detectLocaleFromBrowser(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language') ?? $request->server('HTTP_ACCEPT_LANGUAGE');

        if (! is_string($acceptLanguage) || $acceptLanguage === '') {
            return null;
        }

        $supportedLocales = array_keys((array) config('app.supported_locales', ['en' => 'English']));

        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $acceptLanguage, $matches);

        if (count($matches[1]) === 0) {
            return null;
        }

        /** @var array<string, string|int> $languages */
        $languages = array_combine($matches[1], $matches[4]);

        foreach ($languages as $lang => $val) {
            if ($val === '') {
                $languages[$lang] = 1;
            }
        }

        arsort($languages, SORT_NUMERIC);

        foreach ($languages as $lang => $priority) {
            $code = substr((string) $lang, 0, 2);

            if (in_array($code, $supportedLocales, true)) {
                return $code;
            }
        }

        return null;
    }
}
