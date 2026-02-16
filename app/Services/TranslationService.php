<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * Supported languages with their codes
     */
    public const SUPPORTED_LANGUAGES = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
    ];

    /**
     * Translate text from source language to target language
     * Uses MyMemory Translation API (free tier)
     * 
     * @param string $text
     * @param string $targetLang
     * @param string $sourceLang
     * @return string
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        // Return original if source and target are the same
        if ($sourceLang === $targetLang) {
            return $text;
        }

        // Check cache first
        $cacheKey = "translation:{$sourceLang}:{$targetLang}:" . md5($text);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Use MyMemory Translation API (free, no API key required)
            $response = Http::get('https://api.mymemory.translated.net/get', [
                'q' => $text,
                'langpair' => "{$sourceLang}|{$targetLang}",
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['responseData']['translatedText'])) {
                    $translation = $data['responseData']['translatedText'];
                    
                    // Cache for 30 days
                    Cache::put($cacheKey, $translation, now()->addDays(30));
                    
                    return $translation;
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

        // Return original text if translation fails
        return $text;
    }

    /**
     * Translate an array of texts
     * 
     * @param array $texts
     * @param string $targetLang
     * @param string $sourceLang
     * @return array
     */
    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array
    {
        $translations = [];
        
        foreach ($texts as $key => $text) {
            if (is_array($text)) {
                $translations[$key] = $this->translateBatch($text, $targetLang, $sourceLang);
            } else {
                $translations[$key] = $this->translate($text, $targetLang, $sourceLang);
                
                // Add a small delay to respect API rate limits
                usleep(100000); // 0.1 second delay
            }
        }
        
        return $translations;
    }

    /**
     * Get list of supported languages
     * 
     * @return array
     */
    public function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }

    /**
     * Check if a language is supported
     * 
     * @param string $langCode
     * @return bool
     */
    public function isLanguageSupported(string $langCode): bool
    {
        return array_key_exists($langCode, self::SUPPORTED_LANGUAGES);
    }
}
