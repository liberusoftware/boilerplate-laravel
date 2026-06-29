<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ModuleResource\Pages\ListModules;
use App\Modules\ModuleManager;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ModuleResource extends Resource
{
    protected static ?string $model = null;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

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
            ->records(fn (): Collection => collect(app(ModuleManager::class)->getAllModulesInfo())->keyBy('name'))
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
            ->recordActions([
                Action::make('toggle')
                    ->label(fn (array $record) => $record['enabled'] ? 'Disable' : 'Enable')
                    ->icon(fn (array $record) => $record['enabled'] ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (array $record) => $record['enabled'] ? 'danger' : 'success')
                    ->action(function (array $record) {
                        $moduleManager = app(ModuleManager::class);

                        if ($record['enabled']) {
                            $moduleManager->disable($record['name']);
                        } else {
                            $moduleManager->enable($record['name']);
                        }
                    })
                    ->requiresConfirmation(),
                Action::make('install')
                    ->label('Install')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function (array $record) {
                        $moduleManager = app(ModuleManager::class);
                        $moduleManager->install($record['name']);
                    })
                    ->visible(fn (array $record) => ! $record['enabled'])
                    ->requiresConfirmation(),
                Action::make('uninstall')
                    ->label('Uninstall')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(function (array $record) {
                        $moduleManager = app(ModuleManager::class);
                        $moduleManager->uninstall($record['name']);
                    })
                    ->visible(fn (array $record) => $record['enabled'])
                    ->requiresConfirmation(),
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
                                $moduleManager->enable($record['name']);
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
                                $moduleManager->disable($record['name']);
                            }
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModules::route('/'),
        ];
    }
}
