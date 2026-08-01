<?php

namespace Liberu\Foundation\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Liberu\Foundation\Observability\Contracts\ObservabilityActor;

final class FoundationOperations extends Page
{
    protected string $view = 'foundation-filament::pages.foundation-operations';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Foundation Operations';

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    public array $modules = [];

    public array $diagnostics = [];

    public function mount(ModuleRegistry $registry): void
    {
        $this->modules = array_map(fn ($manifest) => $manifest->toArray(), $registry->all());
        foreach (['failed_jobs', 'feature_flags', 'webhook_deliveries', 'analytics_deliveries', 'data_transfers', 'activity_log'] as $table) {
            $this->diagnostics[$table] = Schema::hasTable($table) ? DB::table($table)->count() : null;
        }
    }

    public static function canAccess(): bool
    {
        $actor = auth()->user();

        return $actor instanceof ObservabilityActor && $actor->isAdmin();
    }
}
