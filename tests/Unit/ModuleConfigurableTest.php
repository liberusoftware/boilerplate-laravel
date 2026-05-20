<?php

use App\Modules\Traits\Configurable;
use App\Modules\Contracts\ModuleInterface;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('testmodule', [
        'key1' => 'value1',
        'key2' => 'value2',
        'nested' => [
            'key' => 'nested_value'
        ]
    ]);
});

it('can get configuration values', function () {
    $module = new class implements ModuleInterface {
        use Configurable;

        public function getName(): string { return 'TestModule'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'Test'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return true; }
        public function enable(): void {}
        public function disable(): void {}
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $value = $module->config('key1');
    expect($value)->toBe('value1');
});

it('returns default when key not found', function () {
    $module = new class implements ModuleInterface {
        use Configurable;

        public function getName(): string { return 'TestModule'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'Test'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return true; }
        public function enable(): void {}
        public function disable(): void {}
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $value = $module->config('nonexistent', 'default_value');
    expect($value)->toBe('default_value');
});

it('can set configuration values', function () {
    $module = new class implements ModuleInterface {
        use Configurable;

        public function getName(): string { return 'TestModule'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'Test'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return true; }
        public function enable(): void {}
        public function disable(): void {}
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $module->setConfig('new_key', 'new_value');
    $value = $module->config('new_key');
    expect($value)->toBe('new_value');
});

it('can check if configuration exists', function () {
    $module = new class implements ModuleInterface {
        use Configurable;

        public function getName(): string { return 'TestModule'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'Test'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return true; }
        public function enable(): void {}
        public function disable(): void {}
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    expect($module->hasConfig('key1'))->toBeTrue();
    expect($module->hasConfig('nonexistent'))->toBeFalse();
});

it('can get all configuration', function () {
    $module = new class implements ModuleInterface {
        use Configurable;

        public function getName(): string { return 'TestModule'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'Test'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return true; }
        public function enable(): void {}
        public function disable(): void {}
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $allConfig = $module->getAllConfig();
    expect($allConfig)->toBeArray();
    expect($allConfig)->toHaveKey('key1');
    expect($allConfig)->toHaveKey('key2');
    expect($allConfig['key1'])->toBe('value1');
});

it('can merge configuration', function () {
    $module = new class implements ModuleInterface {
        use Configurable;

        public function getName(): string { return 'TestModule'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'Test'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return true; }
        public function enable(): void {}
        public function disable(): void {}
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $module->mergeConfig(['key3' => 'value3', 'key4' => 'value4']);
    
    expect($module->config('key3'))->toBe('value3');
    expect($module->config('key4'))->toBe('value4');
    expect($module->config('key1'))->toBe('value1'); // Original should still exist
});

it('handles nested configuration keys', function () {
    $module = new class implements ModuleInterface {
        use Configurable;

        public function getName(): string { return 'TestModule'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'Test'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return true; }
        public function enable(): void {}
        public function disable(): void {}
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $value = $module->config('nested.key');
    expect($value)->toBe('nested_value');
});
