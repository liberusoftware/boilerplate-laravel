<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\ThemeManager;

class ThemeSwitcher extends Component
{
    public $currentTheme;
    public $availableThemes = [];

    public function mount()
    {
        $themeManager = app(ThemeManager::class);
        $this->currentTheme = $themeManager->getActiveTheme();
        $this->availableThemes = $themeManager->getThemes();
    }

    public function switchTheme($theme)
    {
        $themeManager = app(ThemeManager::class);
        
        if ($themeManager->themeExists($theme)) {
            set_theme($theme);
            $this->currentTheme = $theme;
            
            // Dispatch browser event to reload the page
            $this->dispatch('theme-changed', theme: $theme);
            
            // Show success message
            session()->flash('message', 'Theme changed successfully!');
            
            // Refresh the page to apply new theme
            return redirect()->to(request()->header('Referer'));
        }
    }

    public function render()
    {
        return view('livewire.theme-switcher');
    }
}
