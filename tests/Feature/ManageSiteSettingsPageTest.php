<?php

use App\Models\User;
use Filament\Facades\Filament;
use Liberu\Foundation\Settings\Settings\SiteSettings;
use Liberu\Foundation\SettingsFilament\Pages\ManageSiteSettings;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());
});

it('renders the manage site settings page', function () {
    Livewire::test(ManageSiteSettings::class)->assertOk();
});

it('persists site settings from the form', function () {
    Livewire::test(ManageSiteSettings::class)
        ->fillForm([
            'site_name' => 'Acme',
            'site_email' => 'info@acme.test',
            'footer_copyright' => '© Acme',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SiteSettings::class);

    expect(app(SiteSettings::class)->site_name)->toBe('Acme');
});
