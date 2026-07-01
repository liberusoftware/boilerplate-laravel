<?php

use App\Models\User;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Spatie\Permission\Traits\HasRoles;

it('uses the Spatie HasRoles trait', function () {
    expect(class_uses_recursive(User::class))->toContain(HasRoles::class)
        ->and(method_exists(User::class, 'assignRole'))->toBeTrue();
});

it('implements the Filament tenancy contracts', function () {
    $contracts = class_implements(User::class);

    expect($contracts)->toContain(FilamentUser::class)
        ->toContain(HasTenants::class)
        ->toContain(HasDefaultTenant::class);
});
