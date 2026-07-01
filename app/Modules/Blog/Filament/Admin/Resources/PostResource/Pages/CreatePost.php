<?php

namespace App\Modules\Blog\Filament\Admin\Resources\PostResource\Pages;

use App\Modules\Blog\Filament\Admin\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    /**
     * Posts belong to an author (user_id is NOT NULL and not on the form),
     * so default it to the acting user.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] ??= auth()->id();

        return $data;
    }
}
