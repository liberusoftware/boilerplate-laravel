<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Blog\Core\Models\Post;
use Liberu\Blog\Filament\Resources\PostResource\Pages\CreatePost;
use Liberu\Foundation\Authorization\Models\Role;
use Liberu\Foundation\Organizations\Models\Team;
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
