<?php

use App\Modules\Support\ExternalModuleLoader;
use App\Modules\ModuleManager;
use Illuminate\Support\Facades\File;

it('can load modules from a custom path', function () {
    $moduleManager = new ModuleManager();
    $loader = new ExternalModuleLoader($moduleManager);

    // Use the existing BlogModule for testing
    $modulesPath = app_path('Modules');
    
    if (File::exists($modulesPath)) {
        $loader->loadFromPath($modulesPath, 'App\Modules');
        
        $loadedPaths = $loader->getLoadedPaths();
        expect($loadedPaths)->toContain($modulesPath);
    }
})->skip(!File::exists(app_path('Modules')), 'Modules directory does not exist');

it('prevents loading the same path multiple times', function () {
    $moduleManager = new ModuleManager();
    $loader = new ExternalModuleLoader($moduleManager);

    $modulesPath = app_path('Modules');
    
    if (File::exists($modulesPath)) {
        $loader->loadFromPath($modulesPath, 'App\Modules');
        $loader->loadFromPath($modulesPath, 'App\Modules'); // Second call
        
        $loadedPaths = $loader->getLoadedPaths();
        // Should only appear once
        expect(count(array_filter($loadedPaths, fn($p) => $p === $modulesPath)))->toBe(1);
    }
})->skip(!File::exists(app_path('Modules')), 'Modules directory does not exist');

it('handles non-existent paths gracefully', function () {
    $moduleManager = new ModuleManager();
    $loader = new ExternalModuleLoader($moduleManager);

    $loader->loadFromPath('/non/existent/path', 'Test');
    
    // Should not throw and should not add to loaded paths
    expect($loader->getLoadedPaths())->not->toContain('/non/existent/path');
});

it('can register a custom module', function () {
    $moduleManager = new ModuleManager();
    $loader = new ExternalModuleLoader($moduleManager);

    // Use the BlogModule as test subject if it exists
    $blogModulePath = app_path('Modules/BlogModule');
    
    if (File::exists($blogModulePath)) {
        $result = $loader->registerCustomModule(
            $blogModulePath,
            'App\Modules\BlogModule\BlogModule'
        );
        
        expect($result)->toBeTrue();
        expect($moduleManager->has('Blog'))->toBeTrue();
    }
})->skip(!File::exists(app_path('Modules/BlogModule')), 'BlogModule does not exist');

it('returns false when registering module with invalid path', function () {
    $moduleManager = new ModuleManager();
    $loader = new ExternalModuleLoader($moduleManager);

    $result = $loader->registerCustomModule(
        '/invalid/path',
        'InvalidClass'
    );
    
    expect($result)->toBeFalse();
});

it('returns false when registering module with invalid class', function () {
    $moduleManager = new ModuleManager();
    $loader = new ExternalModuleLoader($moduleManager);

    $result = $loader->registerCustomModule(
        app_path('Modules'),
        'NonExistentModuleClass'
    );
    
    expect($result)->toBeFalse();
});

it('loads modules from composer packages if configured', function () {
    $moduleManager = new ModuleManager();
    $loader = new ExternalModuleLoader($moduleManager);

    // This test verifies the method exists and runs without error
    // Actual loading depends on vendor packages having modules
    $loader->loadFromComposer();
    
    // Should complete without throwing
    expect(true)->toBeTrue();
});
