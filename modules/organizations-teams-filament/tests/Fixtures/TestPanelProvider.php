<?php

namespace Liberu\Foundation\OrganizationsFilament\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Liberu\Foundation\OrganizationsFilament\OrganizationsFilamentPlugin;

/**
 * The panel `TeamResource` needs in order to be a resource at all.
 *
 * This package ships a plugin; the host composes the panel. So the suite
 * composes the smallest panel that registers the plugin the manifest declares,
 * under the id its `presentation.filament` key names.
 *
 * Deliberately not a copy of the host's `AdminPanelProvider`: that one is
 * tenant-scoped to a `Team`, gated by Shield and themed from site settings, and
 * reproducing it would assert on the host's composition rather than on this
 * resource.
 */
final class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugins([OrganizationsFilamentPlugin::make()]);
    }
}
