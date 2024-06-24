<?php

namespace App\Providers;

use App\Http\Livewire\CreateTeam;
use App\Http\Livewire\DescendantChartComponent;
use App\Http\Livewire\DevilliersReport;
use App\Http\Livewire\EditProfile;
use App\Http\Livewire\PedigreeChart;
use App\Http\Livewire\PeopleSearch;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Livewire components here
        Livewire::component('devilliers-report', DevilliersReport::class);
        Livewire::component('descendant-chart-component', DescendantChartComponent::class);
        Livewire::component('people-search', PeopleSearch::class);
        Livewire::component('pedigree-chart', PedigreeChart::class);
        Livewire::component('create-team', CreateTeam::class);
        Livewire::component('edit-profile', EditProfile::class);
    }
}
