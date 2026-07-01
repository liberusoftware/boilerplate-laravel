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

        // Refresh the current page, but never trust the raw Referer (open-redirect).
        $referer = request()->header('Referer');
        $base = url('/');
        $this->redirect(is_string($referer) && str_starts_with($referer, $base) ? $referer : '/');
    }

    public function render(): View
    {
        return view('livewire.theme-switcher');
    }
}
