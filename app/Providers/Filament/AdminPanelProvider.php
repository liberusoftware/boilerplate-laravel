<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Middleware\SyncShieldTenant;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Liberu\Blog\Filament\BlogFilamentPlugin;
use Liberu\Foundation\ApplicationCore\Http\Middleware\SecurityHeaders;
use Liberu\Foundation\Filament\FoundationAdminPlugin;
use Liberu\Foundation\IdentityFilament\IdentityFilamentPlugin;
use Liberu\Foundation\Localization\Http\Middleware\SetLocale;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\OrganizationsFilament\OrganizationsFilamentPlugin;
use Liberu\Foundation\SettingsFilament\SettingsFilamentPlugin;
use Liberu\Foundation\Theme\Services\ThemeManager;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(app(ThemeManager::class)->getFilamentColors(app(ThemeManager::class)->getSiteTheme()))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->tenant(Team::class, ownershipRelationship: 'team')
            ->tenantMiddleware([
                SyncShieldTenant::class,
            ], isPersistent: true)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
                SecurityHeaders::class,
            ])
            ->plugins([
                // Do NOT tenant-scope Shield's RoleResource: the admin panel scopes by
                // an ownershipRelationship of 'team', but Liberu\Foundation\Authorization\Models\Role (Spatie) has no
                // team() relation, so scoping it 500s the panel when the nav/badges render.
                // Roles are already team-isolated by Spatie's team_id column; leave the
                // resource unscoped (like every other resource here overrides isScopedToTenant).
                FilamentShieldPlugin::make()
                    ->scopeToTenant(false),
                BlogFilamentPlugin::make(),
                SettingsFilamentPlugin::make(),
                OrganizationsFilamentPlugin::make(),
                IdentityFilamentPlugin::make(),
                FoundationAdminPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
