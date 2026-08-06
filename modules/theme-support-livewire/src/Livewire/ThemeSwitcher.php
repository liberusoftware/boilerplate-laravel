<?php

namespace Liberu\Foundation\ThemeSupportLivewire\Livewire;

use Illuminate\View\View;
use Liberu\Foundation\Theme\Services\ThemeManager;
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

        $themeManager->persistTheme($theme);
        $this->currentTheme = $theme;
        $this->dispatch('theme-changed', theme: $theme);
        session()->flash('message', __('Theme changed successfully.'));

        // Refresh the current page, but never trust the raw Referer (open-redirect).
        $referer = request()->header('Referer');
        $base = url('/');
        $this->redirect(is_string($referer) && str_starts_with($referer, $base) ? $referer : '/');
    }

    public function render(): View
    {
        return view('theme-support-livewire::livewire.theme-switcher');
    }
}
