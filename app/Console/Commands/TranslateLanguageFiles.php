<?php

namespace App\Console\Commands;

use App\Services\TranslationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslateLanguageFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:generate 
                            {--source=en : Source language code}
                            {--target= : Target language code (if not specified, translates to all supported languages)}
                            {--file= : Specific translation file to translate (e.g., messages)}
                            {--force : Overwrite existing translations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate automated translations for language files';

    /**
     * Translation service
     *
     * @var TranslationService
     */
    protected $translationService;

    /**
     * Create a new command instance.
     */
    public function __construct(TranslationService $translationService)
    {
        parent::__construct();
        $this->translationService = $translationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sourceLang = $this->option('source');
        $targetLang = $this->option('target');
        $specificFile = $this->option('file');
        $force = $this->option('force');

        $this->info("Starting translation process...");

        // Get source language path
        $sourcePath = lang_path($sourceLang);
        
        if (!File::exists($sourcePath)) {
            $this->error("Source language directory not found: {$sourcePath}");
            return 1;
        }

        // Determine target languages
        $targetLanguages = $targetLang 
            ? [$targetLang] 
            : array_keys($this->translationService->getSupportedLanguages());

        // Remove source language from targets
        $targetLanguages = array_filter($targetLanguages, fn($lang) => $lang !== $sourceLang);

        foreach ($targetLanguages as $target) {
            $this->info("Translating to {$target}...");
            $this->translateLanguage($sourceLang, $target, $specificFile, $force);
        }

        $this->info("Translation process completed!");
        return 0;
    }

    /**
     * Translate files from source to target language
     *
     * @param string $sourceLang
     * @param string $targetLang
     * @param string|null $specificFile
     * @param bool $force
     * @return void
     */
    protected function translateLanguage(string $sourceLang, string $targetLang, ?string $specificFile, bool $force): void
    {
        $sourcePath = lang_path($sourceLang);
        $targetPath = lang_path($targetLang);

        // Create target directory if it doesn't exist
        if (!File::exists($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

        // Get all PHP files in source directory
        $files = File::files($sourcePath);

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            
            // Skip if specific file is specified and this is not it
            if ($specificFile && $fileName !== "{$specificFile}.php") {
                continue;
            }

            $targetFile = $targetPath . '/' . $fileName;

            // Skip if file exists and force is not set
            if (File::exists($targetFile) && !$force) {
                $this->line("  Skipping {$fileName} (already exists, use --force to overwrite)");
                continue;
            }

            $this->line("  Translating {$fileName}...");

            // Load source translations
            $sourceTranslations = require $file->getPathname();

            // Translate
            $targetTranslations = $this->translationService->translateBatch(
                $sourceTranslations,
                $targetLang,
                $sourceLang
            );

            // Save to file
            $this->saveTranslations($targetFile, $targetTranslations);
            
            $this->line("  ✓ {$fileName} translated successfully");
        }
    }

    /**
     * Save translations to file
     *
     * @param string $filePath
     * @param array $translations
     * @return void
     */
    protected function saveTranslations(string $filePath, array $translations): void
    {
        $content = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
        File::put($filePath, $content);
    }
}
