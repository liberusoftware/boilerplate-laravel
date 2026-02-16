<?php

use App\Services\TranslationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('translation service translates text correctly', function () {
    $service = new TranslationService();
    
    Http::fake([
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => [
                'translatedText' => 'Hola',
            ],
        ], 200),
    ]);
    
    $result = $service->translate('Hello', 'es', 'en');
    
    expect($result)->toBe('Hola');
});

test('translation service returns original text if source and target are same', function () {
    $service = new TranslationService();
    
    $result = $service->translate('Hello', 'en', 'en');
    
    expect($result)->toBe('Hello');
});

test('translation service uses cache', function () {
    Cache::flush();
    
    $service = new TranslationService();
    
    Http::fake([
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => [
                'translatedText' => 'Hola',
            ],
        ], 200),
    ]);
    
    // First call should hit the API
    $result1 = $service->translate('Hello', 'es', 'en');
    
    // Second call should use cache
    $result2 = $service->translate('Hello', 'es', 'en');
    
    expect($result1)->toBe('Hola');
    expect($result2)->toBe('Hola');
    
    // Verify cache was used
    $cacheKey = "translation:en:es:" . md5('Hello');
    expect(Cache::has($cacheKey))->toBeTrue();
});

test('translation service handles API failures gracefully', function () {
    $service = new TranslationService();
    
    Http::fake([
        'api.mymemory.translated.net/*' => Http::response([], 500),
    ]);
    
    $result = $service->translate('Hello', 'es', 'en');
    
    // Should return original text on failure
    expect($result)->toBe('Hello');
});

test('translation service can translate batch', function () {
    $service = new TranslationService();
    
    Http::fake([
        'api.mymemory.translated.net/*' => Http::response([
            'responseData' => [
                'translatedText' => 'Translated',
            ],
        ], 200),
    ]);
    
    $texts = [
        'hello' => 'Hello',
        'world' => 'World',
    ];
    
    $results = $service->translateBatch($texts, 'es', 'en');
    
    expect($results)->toBeArray();
    expect($results)->toHaveKey('hello');
    expect($results)->toHaveKey('world');
});

test('translation service can check if language is supported', function () {
    $service = new TranslationService();
    
    expect($service->isLanguageSupported('en'))->toBeTrue();
    expect($service->isLanguageSupported('es'))->toBeTrue();
    expect($service->isLanguageSupported('fr'))->toBeTrue();
    expect($service->isLanguageSupported('de'))->toBeTrue();
    expect($service->isLanguageSupported('xx'))->toBeFalse();
});

test('translation service returns supported languages', function () {
    $service = new TranslationService();
    
    $languages = $service->getSupportedLanguages();
    
    expect($languages)->toBeArray();
    expect($languages)->toHaveKey('en');
    expect($languages)->toHaveKey('es');
    expect($languages)->toHaveKey('fr');
    expect($languages)->toHaveKey('de');
});
