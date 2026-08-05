<?php

use Filament\Schemas\Schema;
use Liberu\Foundation\IdentityFilament\Resources\UserResource;
use Liberu\Foundation\OrganizationsFilament\Resources\TeamResource;

it('builds every module resource form and page map', function () {
    foreach ([UserResource::class, TeamResource::class] as $resource) {
        $schema = $resource::form(Schema::make());

        expect($schema->getComponents())->not->toBeEmpty()
            ->and($resource::getPages())->toHaveKeys(['index', 'create', 'edit']);
    }
});
