<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * Supported languages with their codes.
     *
     * @var array<string, string>
     */
    public const SUPPORTED_LANGUAGES = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
    ];

    /**
     * Translate text from source language to target language.
     * Uses the MyMemory Translation API (free tier).
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        if ($sourceLang === $targetLang) {
            return $text;
        }

        $cacheKey = "translation:{$sourceLang}:{$targetLang}:".md5($text);

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            return is_string($cached) ? $cached : $text;
        }

        try {
            $response = Http::get('https://api.mymemory.translated.net/get', [
                'q' => $text,
                'langpair' => "{$sourceLang}|{$targetLang}",
            ]);

            if ($response->successful()) {
                $translated = data_get((array) $response->json(), 'responseData.translatedText');

                if (is_string($translated) && $translated !== '') {
                    Cache::put($cacheKey, $translated, now()->addDays(30));

                    return $translated;
                }
            }
        } catch (\Exception $e) {
            Log::error('Translation failed', [
                'text' => $text,
                'source' => $sourceLang,
                'target' => $targetLang,
                'error' => $e->getMessage(),
            ]);
        }

        return $text;
    }

    /**
     * Translate an array of texts (recursively for nested arrays).
     *
     * @param  array<int|string, mixed>  $texts
     * @return array<int|string, mixed>
     */
    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array
    {
        $translations = [];

        foreach ($texts as $key => $text) {
            if (is_array($text)) {
                $translations[$key] = $this->translateBatch($text, $targetLang, $sourceLang);
            } elseif (is_string($text)) {
                $translations[$key] = $this->translate($text, $targetLang, $sourceLang);

                // Respect API rate limits.
                usleep(100000);
            } else {
                $translations[$key] = $text;
            }
        }

        return $translations;
    }

    /**
     * Get the list of supported languages.
     *
     * @return array<string, string>
     */
    public function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }

    /**
     * Check if a language is supported.
     */
    public function isLanguageSupported(string $langCode): bool
    {
        return array_key_exists($langCode, self::SUPPORTED_LANGUAGES);
    }
}
