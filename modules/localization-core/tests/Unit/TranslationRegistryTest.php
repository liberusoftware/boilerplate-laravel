<?php

use Liberu\Foundation\Localization\Translation\TranslationRegistry;
use Liberu\Localization\Contracts\TranslationProvider;

it('registers translation providers behind the neutral contract', function () {
    $provider = new class() implements TranslationProvider
    {
        public function name(): string
        {
            return 'test';
        }

        public function translate(string $text, string $targetLanguage, string $sourceLanguage = 'en'): string
        {
            return "{$targetLanguage}:{$text}";
        }

        public function translateBatch(array $texts, string $targetLanguage, string $sourceLanguage = 'en'): array
        {
            return $texts;
        }

        public function supportedLanguages(): array
        {
            return ['en' => 'English'];
        }
    };
    $registry = new TranslationRegistry();
    $registry->register($provider);

    expect($registry->get('test'))->toBe($provider)
        ->and($registry->all())->toBe(['test' => $provider]);
});

it('rejects duplicate provider names', function () {
    $provider = new class() implements TranslationProvider
    {
        public function name(): string
        {
            return 'test';
        }

        public function translate(string $text, string $targetLanguage, string $sourceLanguage = 'en'): string
        {
            return $text;
        }

        public function translateBatch(array $texts, string $targetLanguage, string $sourceLanguage = 'en'): array
        {
            return $texts;
        }

        public function supportedLanguages(): array
        {
            return [];
        }
    };
    $registry = new TranslationRegistry();
    $registry->register($provider);
    $registry->register($provider);
})->throws(InvalidArgumentException::class);
