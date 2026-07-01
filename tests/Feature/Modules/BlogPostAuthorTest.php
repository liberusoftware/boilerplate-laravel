<?php

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Modules\Blog\Filament\Admin\Resources\PostResource\Pages\CreatePost;
use App\Modules\Blog\Models\Post;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('assigns the acting user as author when creating a post (user_id has no default)', function () {
    $admin = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $admin->id]);
    $admin->forceFill(['current_team_id' => $team->id])->save();
    setPermissionsTeamId($team->id);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'team_id' => $team->id]);
    $admin->assignRole('super_admin');

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($team);

    Livewire::test(CreatePost::class)
        ->fillForm([
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'body' => 'First post.',
            'status' => 'published',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::firstOrFail();
    expect($post->user_id)->toBe($admin->id);
});
