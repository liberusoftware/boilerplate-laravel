<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Liberu\Blog\Core\Models\Post;
use Liberu\Blog\Filament\Resources\PostResource\Pages\EditPost;
use Liberu\Foundation\IdentityFilament\Resources\UserResource\Pages\EditUser;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\OrganizationsFilament\Resources\TeamResource\Pages\EditTeam;
use Livewire\Livewire;

it('builds the edit actions for every module resource', function () {
    $team = Team::factory()->create();
    $admin = User::factory()->create(['current_team_id' => $team->id]);
    $post = Post::query()->create([
        'title' => 'Coverage', 'slug' => 'coverage-edit', 'body' => 'Body',
        'status' => 'draft', 'user_id' => $admin->id,
    ]);
    Gate::before(fn () => true);
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($team);

    Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])->assertOk();
    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])->assertOk();
    Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])->assertOk();
});
