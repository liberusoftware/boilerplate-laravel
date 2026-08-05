<?php

namespace Liberu\Foundation\Search\Tests\Unit;

use Liberu\Foundation\Search\Services\SearchService;
use Liberu\Foundation\Search\Tests\TestCase;

/**
 * config('search.models.post') and .group ship as null: nothing in a default
 * composition registers a post or a group model. Reading a null as a class name
 * is a fatal TypeError, so an unconfigured type must search nothing instead.
 */
final class UnconfiguredModelTest extends TestCase
{
    public function test_searching_an_unconfigured_type_returns_an_empty_page(): void
    {
        config()->set('search.models.post', null);
        config()->set('search.models.group', null);

        $search = new SearchService();

        self::assertSame(0, $search->searchPosts(['query' => 'anything'])->total());
        self::assertSame(0, $search->searchGroups(['query' => 'anything'])->total());
    }

    public function test_search_all_omits_types_that_have_no_model(): void
    {
        config()->set('search.models.post', null);
        config()->set('search.models.group', null);

        $results = (new SearchService())->searchAll(['query' => 'anything', 'types' => ['posts', 'groups']]);

        self::assertSame([], array_keys($results));
    }
}
