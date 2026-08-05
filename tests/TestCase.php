<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Fixtures\Models\Group;
use Tests\Fixtures\Models\Post;

abstract class TestCase extends BaseTestCase
{
    /**
     * Register the search fixture types.
     *
     * `search` ships `models.post` and `models.group` as null and defaults only
     * `models.user`; whichever package brings posts and groups supplies the rest.
     * No installed package does, so the composition tests bring their own.
     *
     * This runs in refreshApplication() rather than afterApplicationCreated()
     * because RefreshDatabase migrates in between, and the fixture tables have to
     * exist by then.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->app['migrator']->path(__DIR__.'/Fixtures/migrations');

        config([
            'search.models.post' => Post::class,
            'search.models.group' => Group::class,
        ]);
    }
}
