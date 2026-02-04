<?php

use App\Modules\ModuleManager;
use App\Modules\Contracts\ModuleInterface;
use Exception;

beforeEach(function () {
    // Nothing global needed for these pure unit tests
});

it('can register modules and expose them via all/has/get', function () {
    $manager = new ModuleManager();

    $mod = new class implements ModuleInterface {
        public function getName(): string { return 'M1'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'd'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return false; }
        public function enable(): void {}
        public function disable(): void {}
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $manager->register($mod);

    expect($manager->has('M1'))->toBeTrue();
    expect($manager->get('M1'))->not->toBeNull();
    expect($manager->all()->count())->toBeGreaterThanOrEqual(1);
});

it('filters enabled and disabled modules', function () {
    $manager = new ModuleManager();

    $enabled = new class implements ModuleInterface {
        private $e = true;
        public function getName(): string { return 'E'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'd'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return $this->e; }
        public function enable(): void { $this->e = true; }
        public function disable(): void { $this->e = false; }
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $disabled = new class implements ModuleInterface {
        private $e = false;
        public function getName(): string { return 'D'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'd'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return $this->e; }
        public function enable(): void { $this->e = true; }
        public function disable(): void { $this->e = false; }
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $manager->register($enabled);
    $manager->register($disabled);

    expect($manager->enabled()->contains(fn($m) => $m->getName() === 'E'))->toBeTrue();
    expect($manager->disabled()->contains(fn($m) => $m->getName() === 'D'))->toBeTrue();
});

it('enables a module and returns true even if persisting fails', function () {
    $manager = new ModuleManager();

    $m = new class implements ModuleInterface {
        private $e = false;
        public function getName(): string { return 'P'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'd'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return $this->e; }
        public function enable(): void { $this->e = true; }
        public function disable(): void { $this->e = false; }
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $manager->register($m);

    $res = $manager->enable('P');
    expect($res)->toBeTrue();
    expect($manager->get('P')->isEnabled())->toBeTrue();
});

it('throws when enabling a module with unmet dependencies', function () {
    $manager = new ModuleManager();

    $a = new class implements ModuleInterface {
        private $e = false;
        public function getName(): string { return 'A'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'A'; }
        public function getDependencies(): array { return ['B']; }
        public function isEnabled(): bool { return $this->e; }
        public function enable(): void { $this->e = true; }
        public function disable(): void { $this->e = false; }
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $b = new class implements ModuleInterface {
        private $e = false;
        public function getName(): string { return 'B'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'B'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return $this->e; }
        public function enable(): void { $this->e = true; }
        public function disable(): void { $this->e = false; }
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $manager->register($a);
    $manager->register($b);

    $this->expectException(Exception::class);
    $manager->enable('A');
});

it('prevents disabling modules that have enabled dependents', function () {
    $manager = new ModuleManager();

    $b = new class implements ModuleInterface {
        private $e = true;
        public function getName(): string { return 'B'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'B'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return $this->e; }
        public function enable(): void { $this->e = true; }
        public function disable(): void { $this->e = false; }
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $a = new class implements ModuleInterface {
        private $e = true;
        public function getName(): string { return 'A'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'A'; }
        public function getDependencies(): array { return ['B']; }
        public function isEnabled(): bool { return $this->e; }
        public function enable(): void { $this->e = true; }
        public function disable(): void { $this->e = false; }
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $manager->register($b);
    $manager->register($a);

    // A depends on B and both are enabled; disabling B should throw
    $this->expectException(Exception::class);
    $manager->disable('B');
});

it('install and uninstall enforce dependency rules', function () {
    $manager = new ModuleManager();

    $m = new class implements ModuleInterface {
        public function getName(): string { return 'I'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'I'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return false; }
        public function enable(): void {}
        public function disable(): void {}
        public function install(): void { /* perform install */ }
        public function uninstall(): void { /* perform uninstall */ }
        public function getConfig(): array { return []; }
    };

    $dep = new class implements ModuleInterface {
        private $e = false;
        public function getName(): string { return 'Dep'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'Dep'; }
        public function getDependencies(): array { return []; }
        public function isEnabled(): bool { return $this->e; }
        public function enable(): void { $this->e = true; }
        public function disable(): void { $this->e = false; }
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $mWithDep = new class implements ModuleInterface {
        public function getName(): string { return 'Mwith'; }
        public function getVersion(): string { return '1.0'; }
        public function getDescription(): string { return 'has dep'; }
        public function getDependencies(): array { return ['Dep']; }
        public function isEnabled(): bool { return false; }
        public function enable(): void {}
        public function disable(): void {}
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return []; }
    };

    $manager->register($m);
    expect($manager->install('I'))->toBeTrue();

    $manager->register($dep);
    $manager->register($mWithDep);

    // install should throw because Dep isn't enabled
    $this->expectException(Exception::class);
    $manager->install('Mwith');

    // Similarly, uninstall should throw if there are dependents enabled
    // enable Dep and enable Mwith then try uninstalling Dep
    $dep->enable();
    $mWithDep->enable();
    $this->expectException(Exception::class);
    $manager->uninstall('Dep');
});

it('provides module info and aggregates all modules info', function () {
    $manager = new ModuleManager();

    $m = new class implements ModuleInterface {
        public function getName(): string { return 'Info'; }
        public function getVersion(): string { return '9.9'; }
        public function getDescription(): string { return 'desc'; }
        public function getDependencies(): array { return ['X']; }
        public function isEnabled(): bool { return false; }
        public function enable(): void {}
        public function disable(): void {}
        public function install(): void {}
        public function uninstall(): void {}
        public function getConfig(): array { return ['a' => 'b']; }
    };

    $manager->register($m);

    $info = $manager->getModuleInfo('Info');
    expect($info['name'])->toBe('Info');
    expect($info['version'])->toBe('9.9');
    expect($info['dependencies'])->toBeArray();

    $all = $manager->getAllModulesInfo();
    expect(array_column($all, 'name'))->toContain('Info');
});

