<?php

namespace App\Modules\Blog\Filament\Admin\Resources\PostResource\Pages;

use App\Modules\Blog\Filament\Admin\Resources\PostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    /**
     * @return array<int, DeleteAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
