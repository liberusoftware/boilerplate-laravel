<?php

namespace App\Providers;

use App\Http\Livewire\DabovilleReport;
use App\Http\Livewire\DescendantChartComponent;
use App\Http\Livewire\HenryReport;
use App\Http\Livewire\PedigreeChart;
use App\Http\Livewire\PeopleSearch;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Panel::registerLivewireComponent('example-component', ExampleComponent::class);
        // Panel::registerLivewireComponent('another-component', AnotherComponent::class);
        Panel::registerLivewireComponent('henry-report', HenryReport::class);
        Panel::registerLivewireComponent('descendant-chart-component', DescendantChartComponent::class);
        Panel::registerLivewireComponent('devilliers-report', DabovilleReport::class);
        Panel::registerLivewireComponent('people-search', PeopleSearch::class);
        Panel::registerLivewireComponent('pedigree-chart', PedigreeChart::class);
        // Register the DescendantChartComponent
    }
}
