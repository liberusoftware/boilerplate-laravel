<?php

namespace App\Modules\Blog\Filament\Admin\Resources\PostResource\Pages;

use App\Modules\Blog\Filament\Admin\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
