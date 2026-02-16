<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageSwitcher extends Component
{
    public $currentLocale;
    public $availableLocales;

    public function mount()
    {
        $this->currentLocale = App::getLocale();
        $this->availableLocales = config('app.supported_locales', ['en' => 'English', 'es' => 'Español', 'fr' => 'Français', 'de' => 'Deutsch']);
    }

    public function switchLanguage($locale)
    {
        if (array_key_exists($locale, $this->availableLocales)) {
            Session::put('locale', $locale);
            
            // Update user preference if authenticated
            if (auth()->check()) {
                auth()->user()->update(['locale' => $locale]);
            }
            
            $this->currentLocale = $locale;
            
            // Redirect to refresh the page with new locale
            return redirect()->to(request()->header('Referer') ?? '/');
        }
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }
}
