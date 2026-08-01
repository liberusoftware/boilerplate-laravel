<?php

use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Liberu\Foundation\Theme\Exceptions\InvalidTheme;
use Liberu\Foundation\Theme\Services\ThemeManager;

it('covers theme manager fallback and utility branches', function () {
    $manager = new ThemeManager();
    expect($manager->selectForSurface('unknown'))->toBe('default')
        ->and($manager->getThemes())->not->toBeEmpty()
        ->and($manager->providers())->not->toBeEmpty()
        ->and($manager->getThemeAssetPath('missing'))->toBeNull()
        ->and($manager->activeEntries())->toContain('resources/js/app.js')
        ->and($manager->getThemeConfig('missing'))->toBe([])
        ->and($manager->primaryColor('missing'))->toBe('amber')
        ->and($manager->hasCustomLayout('missing'))->toBeFalse();

    expect(fn () => $manager->assetUrl('/unsafe'))->toThrow(InvalidTheme::class)
        ->and(fn () => $manager->assetUrl('../unsafe'))->toThrow(InvalidTheme::class)
        ->and(fn () => $manager->inheritanceChain('missing'))->toThrow(InvalidTheme::class);

    app()->forgetInstance(ModuleRegistry::class);
    app()->offsetUnset(ModuleRegistry::class);
    expect($manager->themeIsCompatible('default'))->toBeTrue();

    $cache = sys_get_temp_dir().'/theme-cache-'.bin2hex(random_bytes(5));
    file_put_contents($cache, 'cached');
    config()->set('theme.cache_path', $cache);
    $manager->clearCache();
    expect(is_file($cache))->toBeFalse();
});

it('rejects missing fallback and deployment cache', function () {
    config()->set('theme.fallback', 'absent');
    expect(fn () => new ThemeManager())->toThrow(InvalidTheme::class, 'fallback theme');

    config()->set('theme.fallback', 'default');
    config()->set('theme.cache', true);
    config()->set('theme.cache_path', sys_get_temp_dir().'/absent-cache-'.bin2hex(random_bytes(5)));
    expect(fn () => new ThemeManager())->toThrow(InvalidTheme::class, 'no deployment cache');
});
