<?php

namespace Liberu\Foundation\SettingsFilament\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Liberu\Foundation\SettingsFilament\SettingsFilamentPlugin;

/**
 * The panel `ManageSiteSettings` needs in order to be a page at all.
 *
 * This package ships a plugin; the host composes the panel. So the suite
 * composes the smallest panel that registers the plugin the manifest declares,
 * under the id its `presentation.filament.admin` key names — deliberately not a
 * copy of the host's, which is tenant-scoped, Shield-gated and themed from these
 * very settings.
 */
final class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugins([SettingsFilamentPlugin::make()]);
    }
}
