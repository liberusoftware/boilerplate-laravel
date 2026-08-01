<?php

use App\Models\User;
use Liberu\Foundation\ModuleManager\Manifest;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Liberu\Foundation\ModuleManager\ModuleValidationGuard;
use Liberu\Foundation\ModuleManager\ModuleValidator;
use Liberu\Foundation\Search\Contracts\SearchIndexer;
use Liberu\Foundation\Search\Registry\IndexableRegistry;

it('renders only published posts on the blog index', function () {
    config()->set('blog.posts_per_page', 'invalid');

    $this->get('/blog')->assertOk()->assertViewHas('posts');
});

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

it('reports invalid modules from validation and doctor commands', function () {
    $directory = sys_get_temp_dir().'/invalid-command-'.bin2hex(random_bytes(5));
    mkdir($directory, 0777, true);
    file_put_contents($directory.'/module.json', json_encode([
        'name' => 'invalid-command', 'display_name' => 'Invalid', 'description' => 'Invalid',
        'version' => '1.0.0', 'category' => 'capability', 'provider' => 'Missing\\Provider',
        'requires' => ['packages' => [], 'capabilities' => []], 'capabilities' => [],
        'default_enabled' => true,
    ], JSON_THROW_ON_ERROR));
    file_put_contents($directory.'/composer.json', json_encode(['name' => 'local/invalid-command']));
    $registry = new ModuleRegistry(['invalid-command' => Manifest::fromFile($directory.'/module.json')]);
    app()->instance(ModuleRegistry::class, $registry);

    $this->artisan('module:validate')->assertFailed();
    $this->artisan('foundation:doctor')->assertFailed();
    expect(fn () => (new ModuleValidationGuard(new ModuleValidator()))->ensureValid($registry, app()->version()))
        ->toThrow(RuntimeException::class, 'Module validation failed');
});
