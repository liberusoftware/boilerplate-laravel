<?php

namespace Liberu\Localization\Contracts;

interface TranslationProvider
{
    public function name(): string;

    public function translate(string $text, string $targetLanguage, string $sourceLanguage = 'en'): string;

    /** @param array<int|string, mixed> $texts @return array<int|string, mixed> */
    public function translateBatch(array $texts, string $targetLanguage, string $sourceLanguage = 'en'): array;

    /** @return array<string, string> */
    public function supportedLanguages(): array;
}
