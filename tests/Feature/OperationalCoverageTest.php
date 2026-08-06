<?php

use App\Models\User;
use Liberu\Foundation\Search\Contracts\SearchIndexer;
use Liberu\Foundation\Search\Registry\IndexableRegistry;

it('reindexes every record of a requested search type', function () {
    User::factory()->count(2)->create();
    $registry = new IndexableRegistry();
    $registry->register('users', User::class);
    app()->instance(IndexableRegistry::class, $registry);
    $indexer = Mockery::mock(SearchIndexer::class);
    $indexer->shouldReceive('flush')->once()->with('users');
    $indexer->shouldReceive('index')->twice()->with('users', Mockery::type(User::class));
    app()->instance(SearchIndexer::class, $indexer);

    $this->artisan('search:reindex', ['type' => 'users'])
        ->expectsOutput('Reindexed users.')
        ->assertSuccessful();
});
