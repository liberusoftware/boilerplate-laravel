<?php

namespace Liberu\Foundation\LocalizationLivewire\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Component;

final class LanguageSwitcher extends Component
{
    public string $currentLocale = '';

    /** @var array<string, string> */
    public array $availableLocales = [];

    public function mount(): void
    {
        $this->currentLocale = App::getLocale();
        $this->availableLocales = array_filter((array) config('app.supported_locales', []), 'is_string');
    }

    public function switchLanguage(string $locale): void
    {
        if (! array_key_exists($locale, $this->availableLocales)) {
            return;
        }
        Session::put('locale', $locale);
        if (($user = auth()->user()) instanceof Model) {
            $user->update(['locale' => $locale]);
        }
        $this->currentLocale = $locale;
        $referer = request()->header('Referer');
        $base = url('/');
        $this->redirect(is_string($referer) && str_starts_with($referer, $base) ? $referer : '/');
    }

    public function render(): View
    {
        return view('localization-livewire::livewire.language-switcher');
    }
}
