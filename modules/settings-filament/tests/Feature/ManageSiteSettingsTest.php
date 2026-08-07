<?php

use Illuminate\Support\Facades\DB;
use Liberu\Foundation\Settings\Settings\SiteSettings;
use Liberu\Foundation\SettingsFilament\Pages\ManageSiteSettings;
use Liberu\PackageTestbench\TestUser;
use Livewire\Livewire;

/**
 * The page is filled from the stored settings rather than rendered empty: a
 * settings page with no values mounts successfully whatever is wrong with its
 * form, so it cannot fail on the thing it is named for.
 */
/**
 * `SiteSettings` has thirteen properties and no seeder in this package's tree —
 * the host's migration supplies them — so every one is written here. Loading a
 * settings class with any property missing throws before the page can mount, so
 * a partial fixture would fail on the fixture rather than on the page.
 */
beforeEach(function () {
    $values = [
        'site_name' => 'Analytical Engines',
        'site_email' => 'hello@example.test',
        'site_phone' => null,
        'site_address' => null,
        'site_country' => null,
        'site_currency' => 'GBP',
        'site_default_language' => 'en',
        'facebook_url' => null,
        'twitter_url' => null,
        'github_url' => null,
        'youtube_url' => null,
        'footer_copyright' => '© Analytical Engines',
        'active_theme' => 'base',
    ];

    foreach ($values as $name => $payload) {
        DB::table('settings')->insert([
            'group' => SiteSettings::group(),
            'name' => $name,
            'locked' => false,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
});

it('mounts with the stored settings loaded', function () {
    $this->actingAs(TestUser::factory()->create());

    // assertFormSet rather than assertSee: a settings page's values live in the
    // form state, not as text in the markup, so assertSee would pass on a page
    // that had loaded nothing.
    Livewire::test(ManageSiteSettings::class)
        ->assertOk()
        ->assertFormSet([
            'site_name' => 'Analytical Engines',
            'site_email' => 'hello@example.test',
            'active_theme' => 'base',
        ]);
});

it('offers every discovered theme in the theme select', function () {
    // The select's options come from ThemeManager, so this fails if the page
    // stops resolving it or the discovery returns nothing — which is what would
    // happen if the theme packages were not installed alongside.
    $this->actingAs(TestUser::factory()->create());

    Livewire::test(ManageSiteSettings::class)
        ->assertOk()
        ->assertFormFieldExists('active_theme');
});
