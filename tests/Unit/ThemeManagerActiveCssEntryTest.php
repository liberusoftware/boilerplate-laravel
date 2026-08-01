<?php

use Illuminate\Support\Facades\File;
use Liberu\Foundation\Theme\Services\ThemeManager;

it('selects the built theme bundle when available and otherwise uses the application bundle', function () {
    $manager = app(ThemeManager::class);
    $expected = $manager->viteHasAsset('themes/default/resources/css/app.css')
        ? 'themes/default/resources/css/app.css'
        : 'resources/css/app.css';

    expect($manager->activeCssEntry())->toBe($expected);
});

it('returns the theme bundle path when it is present in the Vite manifest', function () {
    $manifestPath = public_path('build/manifest.json');
    $backup = File::exists($manifestPath) ? File::get($manifestPath) : null;
    File::ensureDirectoryExists(dirname($manifestPath));
    File::put($manifestPath, json_encode([
        'themes/clear-signal/resources/css/app.css' => ['file' => 'assets/clear-signal.css'],
    ]));

    try {
        $manager = app(ThemeManager::class);
        // themeExists('clear-signal') is false until Task 3 adds the dir, so
        // setTheme() would no-op. Set activeTheme directly to isolate the
        // manifest-selection logic under test.
        (fn () => $this->activeTheme = 'clear-signal')->call($manager);

        expect($manager->activeCssEntry())->toBe('themes/clear-signal/resources/css/app.css');
    } finally {
        if ($backup === null) {
            File::delete($manifestPath);
        } else {
            File::put($manifestPath, $backup);
        }
    }
});
