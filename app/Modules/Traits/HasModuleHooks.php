<?php

namespace App\Modules\Traits;

/**
 * Trait HasModuleHooks
 * 
 * Provides a flexible hook system for modules to extend functionality
 * at various lifecycle points.
 */
trait HasModuleHooks
{
    /**
     * Registered hooks.
     */
    protected array $hooks = [];

    /**
     * Register a hook callback.
     *
     * @param string $hookName The name of the hook
     * @param callable $callback The callback to execute
     * @param int $priority Priority of execution (lower = earlier)
     */
    public function registerHook(string $hookName, callable $callback, int $priority = 10): void
    {
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }

        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        // Sort by priority
        usort($this->hooks[$hookName], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Execute all callbacks for a hook.
     *
     * @param string $hookName The hook name
     * @param mixed ...$args Arguments to pass to callbacks
     * @return mixed The result from the last callback, or null
     */
    public function executeHook(string $hookName, ...$args): mixed
    {
        if (!isset($this->hooks[$hookName])) {
            return null;
        }

        $result = null;
        foreach ($this->hooks[$hookName] as $hook) {
            $result = call_user_func_array($hook['callback'], $args);
        }

        return $result;
    }

    /**
     * Check if a hook has callbacks registered.
     */
    public function hasHook(string $hookName): bool
    {
        return isset($this->hooks[$hookName]) && count($this->hooks[$hookName]) > 0;
    }

    /**
     * Remove all callbacks for a hook.
     */
    public function clearHook(string $hookName): void
    {
        unset($this->hooks[$hookName]);
    }

    /**
     * Get all registered hooks.
     */
    public function getHooks(): array
    {
        return array_keys($this->hooks);
    }
}
