<?php

namespace App\Filament\Admin\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use App\Filament\Admin\Resources\ModuleResource\Pages\ListModules;
use App\Filament\Admin\Resources\ModuleResource\Pages\ViewModule;
use App\Modules\ModuleManager;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\ModuleResource\Pages;

class ModuleResource extends Resource
{
    protected static ?string $model = null;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Modules';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->disabled(),
                TextInput::make('version')
                    ->disabled(),
                Textarea::make('description')
                    ->disabled(),
                Toggle::make('enabled')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery())
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('version')
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50),
                IconColumn::make('enabled')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('dependencies')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->limit(30),
            ])
            ->filters([
                TernaryFilter::make('enabled')
                    ->label('Status')
                    ->trueLabel('Enabled')
                    ->falseLabel('Disabled')
                    ->queries(
                        true: fn (Builder $query) => $query->where('enabled', true),
                        false: fn (Builder $query) => $query->where('enabled', false),
                    ),
            ])
            ->recordActions([
                Action::make('toggle')
                    ->label(fn ($record) => $record->enabled ? 'Disable' : 'Enable')
                    ->icon(fn ($record) => $record->enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->enabled ? 'danger' : 'success')
                    ->action(function ($record) {
                        $moduleManager = app(ModuleManager::class);
                        
                        if ($record->enabled) {
                            $moduleManager->disable($record->name);
                        } else {
                            $moduleManager->enable($record->name);
                        }
                    })
                    ->requiresConfirmation(),
                Action::make('install')
                    ->label('Install')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function ($record) {
                        $moduleManager = app(ModuleManager::class);
                        $moduleManager->install($record->name);
                    })
                    ->visible(fn ($record) => !$record->enabled)
                    ->requiresConfirmation(),
                Action::make('uninstall')
                    ->label('Uninstall')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(function ($record) {
                        $moduleManager = app(ModuleManager::class);
                        $moduleManager->uninstall($record->name);
                    })
                    ->visible(fn ($record) => $record->enabled)
                    ->requiresConfirmation(),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enable')
                        ->label('Enable Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $moduleManager = app(ModuleManager::class);
                            foreach ($records as $record) {
                                $moduleManager->enable($record->name);
                            }
                        })
                        ->requiresConfirmation(),
                    BulkAction::make('disable')
                        ->label('Disable Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $moduleManager = app(ModuleManager::class);
                            foreach ($records as $record) {
                                $moduleManager->disable($record->name);
                            }
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $moduleManager = app(ModuleManager::class);
        $modules = $moduleManager->getAllModulesInfo();

        // Convert modules array to a collection that can be used with Filament
        $query = new class extends Builder {
            protected $modules;

            public function __construct($modules)
            {
                $this->modules = collect($modules);
            }

            public function get($columns = ['*'])
            {
                return $this->modules->map(function ($module) {
                    return (object) $module;
                });
            }

            public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
            {
                return $this->modules->map(function ($module) {
                    return (object) $module;
                });
            }

            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                if ($column === 'enabled') {
                    $this->modules = $this->modules->filter(function ($module) use ($value) {
                        return $module['enabled'] === $value;
                    });
                }
                return $this;
            }
        };

        return new $query($modules);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModules::route('/'),
            'view' => ViewModule::route('/{record}'),
        ];
    }
}