<?php

namespace Tests\Feature;

use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSystemTest extends TestCase
{
    use RefreshDatabase;

    protected ModuleManager $moduleManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moduleManager = app(ModuleManager::class);
    }

    /** @test */
    public function it_can_list_all_modules()
    {
        $modules = $this->moduleManager->all();
        $this->assertNotEmpty($modules);
    }

    /** @test */
    public function it_can_get_module_by_name()
    {
        $module = $this->moduleManager->get('BlogModule');
        $this->assertNotNull($module);
        $this->assertEquals('BlogModule', $module->getName());
    }

    /** @test */
    public function it_can_enable_and_disable_modules()
    {
        $moduleName = 'BlogModule';
        
        // Enable module
        $result = $this->moduleManager->enable($moduleName);
        $this->assertTrue($result);
        
        $module = $this->moduleManager->get($moduleName);
        $this->assertTrue($module->isEnabled());

        // Disable module
        $result = $this->moduleManager->disable($moduleName);
        $this->assertTrue($result);
        
        $module = $this->moduleManager->get($moduleName);
        $this->assertFalse($module->isEnabled());
    }

    /** @test */
    public function it_can_get_module_info()
    {
        $info = $this->moduleManager->getModuleInfo('BlogModule');
        
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('description', $info);
        $this->assertEquals('BlogModule', $info['name']);
    }

    /** @test */
    public function it_can_install_and_uninstall_modules()
    {
        $moduleName = 'BlogModule';
        
        // Install module
        $result = $this->moduleManager->install($moduleName);
        $this->assertTrue($result);
        
        $module = $this->moduleManager->get($moduleName);
        $this->assertTrue($module->isEnabled());

        // Uninstall module
        $result = $this->moduleManager->uninstall($moduleName);
        $this->assertTrue($result);
        
        $module = $this->moduleManager->get($moduleName);
        $this->assertFalse($module->isEnabled());
    }

    /** @test */
    public function it_returns_false_for_non_existent_modules()
    {
        $result = $this->moduleManager->enable('NonExistentModule');
        $this->assertFalse($result);

        $result = $this->moduleManager->disable('NonExistentModule');
        $this->assertFalse($result);

        $module = $this->moduleManager->get('NonExistentModule');
        $this->assertNull($module);
    }
}