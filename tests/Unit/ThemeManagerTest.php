<?php

use App\Services\ThemeManager;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->themeManager = new ThemeManager();
});

test('theme manager loads themes from themes directory', function () {
    $themes = $this->themeManager->getThemes();
    
    expect($themes)->toBeArray()
        ->and($themes)->not->toBeEmpty();
});

test('default theme exists', function () {
    expect($this->themeManager->themeExists('default'))->toBeTrue();
});

test('dark theme exists', function () {
    expect($this->themeManager->themeExists('dark'))->toBeTrue();
});

test('can get active theme', function () {
    $activeTheme = $this->themeManager->getActiveTheme();
    
    expect($activeTheme)->toBeString()
        ->and($activeTheme)->toBe('default');
});

test('can set theme', function () {
    $this->themeManager->setTheme('dark');
    
    expect($this->themeManager->getActiveTheme())->toBe('dark');
});

test('cannot set non-existent theme', function () {
    $initialTheme = $this->themeManager->getActiveTheme();
    $this->themeManager->setTheme('nonexistent');
    
    expect($this->themeManager->getActiveTheme())->toBe($initialTheme);
});

test('can get theme path', function () {
    $themePath = $this->themeManager->getThemePath('default');
    
    expect($themePath)->toContain('themes/default')
        ->and(File::exists($themePath))->toBeTrue();
});

test('can get theme views path', function () {
    $themeViewsPath = $this->themeManager->getThemeViewsPath('default');
    
    expect($themeViewsPath)->toContain('themes/default/views')
        ->and(File::exists($themeViewsPath))->toBeTrue();
});

test('can get theme configuration', function () {
    $config = $this->themeManager->getThemeConfig('default');
    
    expect($config)->toBeArray()
        ->and($config)->toHaveKey('name')
        ->and($config)->toHaveKey('label')
        ->and($config['name'])->toBe('default');
});

test('theme has CSS file', function () {
    $cssPath = $this->themeManager->getThemeCss('default');
    
    expect($cssPath)->not->toBeNull()
        ->and($cssPath)->toContain('themes/default/css/app.css')
        ->and(File::exists(base_path($cssPath)))->toBeTrue();
});

test('theme has JS file', function () {
    $jsPath = $this->themeManager->getThemeJs('default');
    
    expect($jsPath)->not->toBeNull()
        ->and($jsPath)->toContain('themes/default/js/app.js')
        ->and(File::exists(base_path($jsPath)))->toBeTrue();
});

test('theme has custom layout', function () {
    $hasLayout = $this->themeManager->hasCustomLayout('app', 'default');
    
    expect($hasLayout)->toBeTrue();
});

test('can get layout path', function () {
    $layoutPath = $this->themeManager->getLayout('app', 'default');
    
    expect($layoutPath)->toBeString()
        ->and($layoutPath)->toContain('layouts.app');
});

test('theme helper functions work', function () {
    expect(function_exists('theme'))->toBeTrue()
        ->and(function_exists('active_theme'))->toBeTrue()
        ->and(function_exists('theme_asset'))->toBeTrue()
        ->and(function_exists('theme_path'))->toBeTrue()
        ->and(function_exists('theme_views_path'))->toBeTrue()
        ->and(function_exists('set_theme'))->toBeTrue()
        ->and(function_exists('theme_layout'))->toBeTrue();
});

test('active_theme helper returns string', function () {
    $theme = active_theme();
    
    expect($theme)->toBeString();
});

test('theme_asset helper generates URL', function () {
    $url = theme_asset('images/logo.png');
    
    expect($url)->toBeString()
        ->and($url)->toContain('themes/')
        ->and($url)->toContain('images/logo.png');
});

test('theme_path helper returns path', function () {
    $path = theme_path('default');
    
    expect($path)->toBeString()
        ->and($path)->toContain('themes/default');
});

test('theme_views_path helper returns views path', function () {
    $path = theme_views_path('default');
    
    expect($path)->toBeString()
        ->and($path)->toContain('themes/default/views');
});

test('dark theme has correct configuration', function () {
    $config = $this->themeManager->getThemeConfig('dark');
    
    expect($config)->toBeArray()
        ->and($config['name'])->toBe('dark')
        ->and($config['label'])->toBe('Dark Theme')
        ->and($config)->toHaveKey('colors')
        ->and($config['colors']['primary'])->toBe('indigo');
});

test('default theme has correct configuration', function () {
    $config = $this->themeManager->getThemeConfig('default');
    
    expect($config)->toBeArray()
        ->and($config['name'])->toBe('default')
        ->and($config['label'])->toBe('Default Theme')
        ->and($config)->toHaveKey('colors')
        ->and($config['colors']['primary'])->toBe('gray');
});
