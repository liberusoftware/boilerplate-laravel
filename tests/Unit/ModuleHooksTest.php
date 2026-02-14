<?php

use App\Modules\Traits\HasModuleHooks;
use App\Modules\Contracts\ModuleInterface;

it('can register and execute hooks', function () {
    $module = new class implements ModuleInterface {
        use HasModuleHooks;

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

    $called = false;
    $module->registerHook('test_hook', function() use (&$called) {
        $called = true;
    });

    expect($module->hasHook('test_hook'))->toBeTrue();
    
    $module->executeHook('test_hook');
    expect($called)->toBeTrue();
});

it('executes hooks in priority order', function () {
    $module = new class implements ModuleInterface {
        use HasModuleHooks;

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

    $order = [];
    
    $module->registerHook('priority_test', function() use (&$order) {
        $order[] = 'second';
    }, priority: 20);

    $module->registerHook('priority_test', function() use (&$order) {
        $order[] = 'first';
    }, priority: 10);

    $module->registerHook('priority_test', function() use (&$order) {
        $order[] = 'third';
    }, priority: 30);

    $module->executeHook('priority_test');
    
    expect($order)->toBe(['first', 'second', 'third']);
});

it('passes arguments to hook callbacks', function () {
    $module = new class implements ModuleInterface {
        use HasModuleHooks;

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

    $result = null;
    $module->registerHook('with_args', function($arg1, $arg2) use (&$result) {
        $result = $arg1 + $arg2;
    });

    $module->executeHook('with_args', 5, 10);
    expect($result)->toBe(15);
});

it('can clear hooks', function () {
    $module = new class implements ModuleInterface {
        use HasModuleHooks;

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

    $module->registerHook('clearable', function() {});
    expect($module->hasHook('clearable'))->toBeTrue();
    
    $module->clearHook('clearable');
    expect($module->hasHook('clearable'))->toBeFalse();
});

it('returns list of registered hooks', function () {
    $module = new class implements ModuleInterface {
        use HasModuleHooks;

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

    $module->registerHook('hook1', function() {});
    $module->registerHook('hook2', function() {});
    $module->registerHook('hook3', function() {});

    $hooks = $module->getHooks();
    expect($hooks)->toBeArray();
    expect($hooks)->toContain('hook1', 'hook2', 'hook3');
});

it('returns null when executing non-existent hook', function () {
    $module = new class implements ModuleInterface {
        use HasModuleHooks;

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

    $result = $module->executeHook('nonexistent');
    expect($result)->toBeNull();
});

it('returns result from last callback', function () {
    $module = new class implements ModuleInterface {
        use HasModuleHooks;

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

    $module->registerHook('multi_return', function() {
        return 'first';
    });
    $module->registerHook('multi_return', function() {
        return 'second';
    });
    $module->registerHook('multi_return', function() {
        return 'last';
    });

    $result = $module->executeHook('multi_return');
    expect($result)->toBe('last');
});
