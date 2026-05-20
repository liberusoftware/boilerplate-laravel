<?php

namespace App\Filament\Admin\Resources\ModuleResource\Pages;

use Filament\Actions\Action;
use App\Filament\Admin\Resources\ModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModules extends ListRecords
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Modules')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    // Clear module cache and reload
                    cache()->forget('app.modules');
                    $this->redirect(request()->header('Referer'));
                }),
        ];
    }
}