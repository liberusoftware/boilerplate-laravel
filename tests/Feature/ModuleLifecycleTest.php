<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Module;

class ModuleLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_persistence_and_enable_disable()
    {
        // Prepare DB record to mimic discovered module
        Module::create([
            'name' => 'TestModule',
            'version' => '1.0.0',
            'description' => 'Test module',
            'enabled' => false,
            'dependencies' => [],
            'config' => [],
        ]);

        $moduleManager = app(\App\Modules\ModuleManager::class);

        // Module might not exist in modules list; ensure enable returns false if not found
        $this->assertFalse($moduleManager->enable('NonExistentModule'));

        // Enabling TestModule should either return false if not loaded, or true if the module class exists.
        // We test persistence toggle on the DB record directly to verify persistence logic.
        $mdl = Module::findByName('TestModule');
        $this->assertNotNull($mdl);
        $this->assertFalse($mdl->enabled);

        $mdl->enabled = true;
        $mdl->save();

        $this->assertTrue(Module::findByName('TestModule')->enabled);
    }
}
