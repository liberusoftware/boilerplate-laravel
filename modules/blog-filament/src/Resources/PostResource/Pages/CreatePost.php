<?php

namespace Liberu\Blog\Filament\Resources\PostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Blog\Filament\Resources\PostResource;

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
