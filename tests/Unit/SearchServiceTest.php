<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Liberu\Foundation\Search\Registry\SearcherRegistry;
use Liberu\Foundation\Search\Services\SearchService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SearchService::class);
});

describe('searchUsers', function () {
    it('returns paginated users', function () {
        User::factory()->count(5)->create();
        $result = $this->service->searchUsers([]);
        expect($result->total())->toBeGreaterThanOrEqual(5);
    });

    it('filters by search query', function () {
        User::factory()->create(['name' => 'UniqueSearchName']);
        User::factory()->create(['name' => 'SomethingElse']);

        $result = $this->service->searchUsers(['query' => 'UniqueSearch']);
        expect($result->total())->toBe(1);
        expect($result->items()[0]->name)->toBe('UniqueSearchName');
    });

    it('filters by verified status', function () {
        User::factory()->create(['email_verified_at' => now()]);
        User::factory()->create(['email_verified_at' => null]);

        $verified = $this->service->searchUsers(['verified' => true]);
        expect($verified->items())->each(fn ($user) => $user->email_verified_at->not->toBeNull());

        $unverified = $this->service->searchUsers(['verified' => false]);
        expect($unverified->items())->each(fn ($user) => $user->email_verified_at->toBeNull());
    });

    it('respects per_page setting', function () {
        User::factory()->count(20)->create();
        $result = $this->service->searchUsers(['per_page' => 5]);
        expect(count($result->items()))->toBeLessThanOrEqual(5);
    });

    it('orders by specified field', function () {
        User::factory()->create(['name' => 'Zara']);
        User::factory()->create(['name' => 'Adam']);

        $result = $this->service->searchUsers(['order_by' => 'name', 'order_direction' => 'asc']);
        $names = array_column($result->items(), 'name');
        expect($names)->toContain('Adam');
        expect($names[0])->toBe('Adam');
    });
});

describe('searchAll', function () {
    it('covers every registered searcher', function () {
        $result = $this->service->searchAll([]);
        expect(array_keys($result))->toBe(app(SearcherRegistry::class)->types());
    });

    it('can limit to specific types', function () {
        app(SearcherRegistry::class)->register('widgets', fn () => new LengthAwarePaginator([], 0, 15));

        $result = $this->service->searchAll(['types' => ['users']]);
        expect($result)->toHaveKey('users');
        expect($result)->not->toHaveKey('widgets');
    });
});
