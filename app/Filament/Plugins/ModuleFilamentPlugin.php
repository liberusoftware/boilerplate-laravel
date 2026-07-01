<?php

namespace App\Filament\Plugins;

use App\Models\Module;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\File;

final class ModuleFilamentPlugin implements Plugin
{
    protected string $segment = 'Admin';

    public static function make(): static
    {
        return new self();
    }

    /** Set which module Filament subfolder this panel discovers (e.g. 'Admin' or 'App'). */
    public function for(string $segment): static
    {
        $this->segment = $segment;

        return $this;
    }

    public function getId(): string
    {
        return 'modules-'.strtolower($this->segment);
    }

    public function register(Panel $panel): void
    {
        $modulesPath = app_path('Modules');

        if (! File::isDirectory($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $modulePath) {
            $name = basename($modulePath);

            if (! $this->isEnabled($name)) {
                continue;
            }

            $base = $modulePath.'/Filament/'.$this->segment;
            $namespace = 'App\\Modules\\'.$name.'\\Filament\\'.$this->segment;

            if (File::isDirectory($base.'/Resources')) {
                $panel->discoverResources(in: $base.'/Resources', for: $namespace.'\\Resources');
            }
            if (File::isDirectory($base.'/Pages')) {
                $panel->discoverPages(in: $base.'/Pages', for: $namespace.'\\Pages');
            }
            if (File::isDirectory($base.'/Widgets')) {
                $panel->discoverWidgets(in: $base.'/Widgets', for: $namespace.'\\Widgets');
            }
        }
    }

    public function boot(Panel $panel): void {}

    /** Enabled state from the modules table; default enabled when the table/row is absent. */
    protected function isEnabled(string $name): bool
    {
        try {
            $record = Module::where('name', $name)->first();

            return $record !== null ? (bool) $record->enabled : true;
        } catch (\Throwable) {
            return true;
        }
    }
}
