<?php

use Liberu\Foundation\Search\Services\SearchService;
use Liberu\PackageTestbench\TestUser;

beforeEach(function () {
    TestUser::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.test']);
    TestUser::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.test']);
});

it('matches the term against name and email', function () {
    expect(app(SearchService::class)->searchUsers(['query' => 'Ada'])->pluck('name')->all())
        ->toBe(['Ada Lovelace']);

    expect(app(SearchService::class)->searchUsers(['query' => 'grace@'])->pluck('name')->all())
        ->toBe(['Grace Hopper']);
});

/**
 * `role()` is Spatie's `HasRoles` scope. This package neither requires nor
 * declares it, so a model without the trait used to fatal here rather than
 * simply not offering the filter — the same shape of defect as calling
 * `search()` on a model that never had it.
 */
it('skips the role filter when the model has no role scope', function () {
    expect(app(SearchService::class)->searchUsers(['role' => 'admin'])->total())->toBe(2);
});

it('still applies the filters that need no scope at all', function () {
    TestUser::factory()->create([
        'name' => 'Unverified Person',
        'email' => 'pending@example.test',
        'email_verified_at' => null,
    ]);

    expect(app(SearchService::class)->searchUsers(['verified' => false])->pluck('name')->all())
        ->toBe(['Unverified Person'])
        ->and(app(SearchService::class)->searchUsers(['verified' => true])->total())->toBe(2);
});
