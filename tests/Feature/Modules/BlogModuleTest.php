<?php

use App\Models\User;
use App\Modules\Blog\Filament\Admin\Resources\PostResource;
use App\Modules\Blog\Models\Post;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('loads the blog module: migration, view namespace, config and route', function () {
    // module_blog_posts table migrated by the module's migration (loadMigrationsFrom).
    // Named module_blog_posts (not posts) because a core `posts` table already exists
    // (database/migrations/2026_02_14_000001_create_posts_table.php, backing App\Models\Post).
    expect(Schema::hasTable('module_blog_posts'))->toBeTrue();

    // config merged under the snake-cased module name.
    expect(config('blog.posts_per_page'))->toBe(10);

    // view namespace registered.
    expect(view()->exists('blog::index'))->toBeTrue();

    // named web route registered and renders.
    $user = User::factory()->create();
    Post::create(['title' => 'Hello', 'slug' => 'hello', 'body' => 'World', 'status' => 'published', 'user_id' => $user->id]);
    $this->get(route('blog.index'))->assertOk()->assertSee('Hello');
});

it('registers the Post admin resource on /admin only', function () {
    expect(Filament::getPanel('admin')->getResources())
        ->toContain(PostResource::class);
    expect(Filament::getPanel('app')->getResources())
        ->not->toContain(PostResource::class);
});
