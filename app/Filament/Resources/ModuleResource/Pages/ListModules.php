<?php

namespace App\Filament\Resources\ModuleResource\Pages;

use App\Filament\Resources\ModuleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListModules extends ListRecords
{
    protected static string $resource = ModuleResource::class;

    /**
     * Modules are an array data source (not Eloquent), so drop the default
     * row-click closures that type-hint a Model and would fail on arrays.
     */
    protected function makeTable(): Table
    {
        return parent::makeTable()
            ->recordUrl(null)
            ->recordAction(null);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Modules')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    // Clear module cache and reload
                    cache()->forget('app.modules');
                    $this->redirect((string) request()->header('Referer'));
                }),
        ];
    }
}
