<?php

namespace App\Filament\Resources\ModuleResource\Pages;

use App\Filament\Resources\ModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewModule extends ViewRecord
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle')
                ->label(fn () => $this->record->enabled ? 'Disable' : 'Enable')
                ->icon(fn () => $this->record->enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->enabled ? 'danger' : 'success')
                ->action(function () {
                    $moduleManager = app(\App\Modules\ModuleManager::class);
                    
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