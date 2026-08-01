<?php

use Liberu\Foundation\Localization\MyMemory\TranslationService;
use Liberu\Localization\Contracts\TranslationProvider;

it('implements the neutral provider contract and short-circuits same-language translation', function () {
    $provider = new TranslationService();

    expect($provider)->toBeInstanceOf(TranslationProvider::class)
        ->and($provider->name())->toBe('mymemory')
        ->and($provider->translate('Hello', 'en', 'en'))->toBe('Hello')
        ->and($provider->supportedLanguages())->toHaveKey('en');
});
