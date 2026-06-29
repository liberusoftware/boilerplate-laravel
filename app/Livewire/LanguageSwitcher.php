<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $currentLocale = '';

    /** @var array<string, string> */
    public array $availableLocales = [];

    public function mount(): void
    {
        $this->currentLocale = App::getLocale();

        $supported = config('app.supported_locales', []);
        $locales = [];

        if (is_array($supported)) {
            foreach ($supported as $code => $label) {
                if (is_string($label)) {
                    $locales[(string) $code] = $label;
                }
            }
        }

        $this->availableLocales = $locales;
    }

    public function switchLanguage(string $locale): void
    {
        if (! array_key_exists($locale, $this->availableLocales)) {
            return;
        }

        Session::put('locale', $locale);

        if (($user = auth()->user()) instanceof User) {
            $user->update(['locale' => $locale]);
        }

        $this->currentLocale = $locale;

        // Refresh the current page, but never trust the raw Referer (open-redirect).
        $referer = request()->header('Referer');
        $base = url('/');
        $this->redirect(is_string($referer) && str_starts_with($referer, $base) ? $referer : '/');
    }

    public function render(): View
    {
        return view('livewire.language-switcher');
    }
}
