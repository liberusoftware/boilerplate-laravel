<?php

namespace Liberu\Foundation\Localization\Context;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

final class LocaleResolver
{
    public function resolve(Request $request): LocaleContext
    {
        $supported = (array) config('localization.locales', config('app.supported_locales', ['en' => 'English']));
        $actor = auth()->user();
        $candidates = [$request->input('locale'), Session::get('locale'), $actor?->locale, config('localization.team_locale'), config('localization.site_locale'), $request->getPreferredLanguage(array_keys($supported)), config('app.fallback_locale'), config('app.locale', 'en')];
        $locale = 'en';
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && array_key_exists($candidate, $supported)) {
                $locale = $candidate;
                break;
            }
        }$timezone = $actor?->timezone ?? config('localization.team_timezone') ?? config('localization.site_timezone', 'UTC');
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = 'UTC';
        }

        return new LocaleContext($locale, $timezone, in_array($locale, (array) config('localization.rtl_locales', []), true) ? 'rtl' : 'ltr');
    }
}
