<?php

use Illuminate\Contracts\View\Factory;
use Illuminate\Filesystem\Filesystem;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Liberu\Foundation\Theme\Cache\ThemeCache;
use Liberu\Foundation\Theme\Discovery\ThemeDiscovery;
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

it('loads themes from the deployment cache', function () {
    $path = sys_get_temp_dir().'/themes-'.bin2hex(random_bytes(5)).'.cache';
    (new ThemeCache())->write((new ThemeDiscovery())->discover(base_path('themes')), $path);
    config()->set('theme.cache', true);
    config()->set('theme.cache_path', $path);

    expect((new ThemeManager())->themeExists('default'))->toBeTrue();
});

it('rejects cyclic theme inheritance', function () {
    $root = sys_get_temp_dir().'/cyclic-themes-'.bin2hex(random_bytes(5));
    $files = new Filesystem();
    $files->copyDirectory(base_path('themes/default'), $root.'/default');
    $files->copyDirectory(base_path('themes/dark'), $root.'/dark');
    foreach (['default' => 'dark', 'dark' => 'default'] as $name => $parent) {
        $manifest = json_decode($files->get($root.'/'.$name.'/theme.json'), true, flags: JSON_THROW_ON_ERROR);
        $manifest['parent'] = $parent;
        $files->put($root.'/'.$name.'/theme.json', json_encode($manifest, JSON_THROW_ON_ERROR));
    }

    expect(fn () => new ThemeManager($root))->toThrow(InvalidTheme::class, 'inheritance cycle');
});

it('handles a non-file view finder and a missing Vite manifest', function () {
    $manager = new ThemeManager();
    $view = View::getFacadeRoot();
    $factory = Mockery::mock(Factory::class);
    $factory->shouldReceive('getFinder')->andReturn(new stdClass());
    View::swap($factory);
    try {
        $manager->registerThemePaths();
    } finally {
        View::swap($view);
    }

    $manifest = public_path('build/manifest.json');
    $backup = $manifest.'.coverage-backup';
    $files = new Filesystem();
    if ($files->isFile($manifest)) {
        $files->move($manifest, $backup);
    }
    try {
        expect($manager->viteHasAsset('missing'))->toBeFalse();
    } finally {
        if ($files->isFile($backup)) {
            $files->move($backup, $manifest);
        }
    }
});

it('returns null when an installed theme declares no assets', function () {
    $root = sys_get_temp_dir().'/empty-theme-'.bin2hex(random_bytes(5));
    $files = new Filesystem();
    $files->copyDirectory(base_path('themes/default'), $root.'/default');
    $manifest = json_decode($files->get($root.'/default/theme.json'), true, flags: JSON_THROW_ON_ERROR);
    $manifest['parent'] = null;
    $manifest['assets'] = ['css' => [], 'js' => []];
    $files->put($root.'/default/theme.json', json_encode($manifest, JSON_THROW_ON_ERROR));

    expect((new ThemeManager($root))->getThemeCss())->toBeNull();
});
