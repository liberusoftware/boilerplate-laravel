<?php

use App\Services\ThemeManager;
use Illuminate\Support\Facades\File;

it('falls back to the main app.css when the active theme has no built bundle', function () {
    // Default test env has no Vite manifest → viteHasAsset() is false.
    expect(app(ThemeManager::class)->activeCssEntry())->toBe('resources/css/app.css');
});

it('returns the theme bundle path when it is present in the Vite manifest', function () {
    $manifestPath = public_path('build/manifest.json');
    $backup = File::exists($manifestPath) ? File::get($manifestPath) : null;
    File::ensureDirectoryExists(dirname($manifestPath));
    File::put($manifestPath, json_encode([
        'themes/clear-signal/css/app.css' => ['file' => 'assets/clear-signal.css'],
    ]));

    try {
        $manager = app(ThemeManager::class);
        // themeExists('clear-signal') is false until Task 3 adds the dir, so
        // setTheme() would no-op. Set activeTheme directly to isolate the
        // manifest-selection logic under test.
        (fn () => $this->activeTheme = 'clear-signal')->call($manager);

        expect($manager->activeCssEntry())->toBe('themes/clear-signal/css/app.css');
    } finally {
        if ($backup === null) {
            File::delete($manifestPath);
        } else {
            File::put($manifestPath, $backup);
        }
    }
});
