<?php

use App\Filament\Admin\Resources\GroupResource;
use App\Filament\Admin\Resources\GroupResource\Pages\ListGroups;
use App\Filament\Admin\Resources\MenuItemResource;
use App\Filament\Admin\Resources\MenuResource;
use App\Filament\Admin\Resources\PostResource;
use App\Filament\Admin\Resources\PostResource\Pages\ListPosts;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// These models have no team relationship, so under the tenant-scoped admin
// panel they must opt out of tenancy or Filament 500s resolving Model->team().
it('opts global resources out of tenant scoping', function () {
    expect(PostResource::isScopedToTenant())->toBeFalse()
        ->and(GroupResource::isScopedToTenant())->toBeFalse()
        ->and(MenuResource::isScopedToTenant())->toBeFalse()
        ->and(MenuItemResource::isScopedToTenant())->toBeFalse();
});

it('renders the posts and groups lists under the tenant panel', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->id]);

    Gate::before(fn () => true);
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($team);

    Livewire::test(ListPosts::class)->assertOk();
    Livewire::test(ListGroups::class)->assertOk();
});
