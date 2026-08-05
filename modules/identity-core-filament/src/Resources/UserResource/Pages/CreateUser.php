<?php

namespace Liberu\Foundation\IdentityFilament\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Foundation\IdentityFilament\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
