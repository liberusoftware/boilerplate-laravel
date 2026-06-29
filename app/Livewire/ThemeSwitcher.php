<?php

namespace App\Livewire;

use App\Services\ThemeManager;
use Illuminate\View\View;
use Livewire\Component;

class ThemeSwitcher extends Component
{
    public ?string $currentTheme = null;

    /** @var array<string, array<string, mixed>> */
    public array $availableThemes = [];

    public function mount(): void
    {
        $themeManager = app(ThemeManager::class);
        $this->currentTheme = $themeManager->getActiveTheme();
        $this->availableThemes = $themeManager->getThemes();
    }

    public function switchTheme(string $theme): void
    {
        $themeManager = app(ThemeManager::class);

        if (! $themeManager->themeExists($theme)) {
            return;
        }

        set_theme($theme);
        $this->currentTheme = $theme;
        $this->dispatch('theme-changed', theme: $theme);
        session()->flash('message', 'Theme changed successfully!');

        $referer = request()->header('Referer');
        $this->redirect(is_string($referer) ? $referer : '/');
    }

    public function render(): View
    {
        return view('livewire.theme-switcher');
    }
}
