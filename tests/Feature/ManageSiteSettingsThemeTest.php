<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Models\User;
use App\Settings\SiteSettings;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());
});

it('renders the active_theme field', function () {
    Livewire::test(ManageSiteSettings::class)
        ->assertOk()
        ->assertFormFieldExists('active_theme');
});

it('persists a chosen theme', function () {
    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['active_theme' => 'dark'])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SiteSettings::class);

    expect(app(SiteSettings::class)->active_theme)->toBe('dark');
});
