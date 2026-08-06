<?php

namespace Liberu\Foundation\Search\Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Liberu\Foundation\Search\Registry\SearcherRegistry;
use Liberu\Foundation\Search\Services\SearchService;
use Liberu\Foundation\Search\Tests\TestCase;
use Liberu\PackageTestbench\TestUser;

/**
 * `searchAll()` used to name users, posts and groups in its body, which made
 * `search` unable to ship without the package that owned the last two. What it
 * offers is now whatever registered, so these tests are about the seam rather
 * than about any particular type: `search` contributes `users`, and a stand-in
 * for a package that contributes its own proves nothing else had to change.
 */
final class SearcherRegistryTest extends TestCase
{
    public function test_search_all_returns_only_the_types_the_composition_registered(): void
    {
        TestUser::factory()->count(2)->create();

        $results = $this->app->make(SearchService::class)->searchAll([]);

        self::assertSame(['users'], array_keys($results));
        self::assertSame(2, $results['users']->total());
    }

    public function test_a_registered_searcher_joins_search_all(): void
    {
        $this->registerWidgets();

        $results = $this->app->make(SearchService::class)->searchAll(['per_page' => 7]);

        self::assertSame(['users', 'widgets'], array_keys($results));
        self::assertSame(7, $results['widgets']->perPage());
    }

    public function test_search_all_honours_a_requested_subset_of_types(): void
    {
        $this->registerWidgets();

        $results = $this->app->make(SearchService::class)->searchAll(['types' => ['widgets']]);

        self::assertSame(['widgets'], array_keys($results));
    }

    public function test_a_type_cannot_be_claimed_twice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->app->make(SearcherRegistry::class)->register('users', fn (array $filters) => $this->emptyPage());
    }

    private function registerWidgets(): void
    {
        $this->app->make(SearcherRegistry::class)->register(
            'widgets',
            fn (array $filters): LengthAwarePaginator => new LengthAwarePaginator(['widget'], 1, (int) $filters['per_page']),
        );
    }

    /**
     * @return LengthAwarePaginator<int, mixed>
     */
    private function emptyPage(): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 15);
    }
}
