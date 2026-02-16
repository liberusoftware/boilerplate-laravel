<?php

use App\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

test('language switcher component can switch language', function () {
    Livewire::test(\App\Livewire\LanguageSwitcher::class)
        ->call('switchLanguage', 'es')
        ->assertRedirect();
    
    expect(Session::get('locale'))->toBe('es');
});

test('language switcher validates language code', function () {
    Session::flush();
    
    Livewire::test(\App\Livewire\LanguageSwitcher::class)
        ->call('switchLanguage', 'invalid');
    
    // Should not set invalid locale in session
    expect(Session::get('locale'))->toBeNull();
});

test('language switcher updates user preference when authenticated', function () {
    $user = User::factory()->create(['locale' => 'en']);
    
    $this->actingAs($user);
    
    Livewire::test(\App\Livewire\LanguageSwitcher::class)
        ->call('switchLanguage', 'fr')
        ->assertRedirect();
    
    expect($user->fresh()->locale)->toBe('fr');
});

test('language switcher displays current locale', function () {
    app()->setLocale('es');
    
    Livewire::test(\App\Livewire\LanguageSwitcher::class)
        ->assertSet('currentLocale', 'es');
});

test('language switcher displays available locales', function () {
    Livewire::test(\App\Livewire\LanguageSwitcher::class)
        ->assertSet('availableLocales', [
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'de' => 'Deutsch',
        ]);
});
