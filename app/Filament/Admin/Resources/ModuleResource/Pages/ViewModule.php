<?php

namespace App\Filament\Admin\Resources\ModuleResource\Pages;

use Filament\Actions\Action;
use App\Modules\ModuleManager;
use App\Filament\Admin\Resources\ModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewModule extends ViewRecord
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggle')
                ->label(fn () => $this->record->enabled ? 'Disable' : 'Enable')
                ->icon(fn () => $this->record->enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->enabled ? 'danger' : 'success')
                ->action(function () {
                    $moduleManager = app(ModuleManager::class);
                    
                    if ($this->record->enabled) {
                        $moduleManager->disable($this->record->name);
                    } else {
                        $moduleManager->enable($this->record->name);
                    }
                    
                    $this->redirect(static::getResource()::getUrl('index'));
                })
                ->requiresConfirmation(),
        ];
    }
}