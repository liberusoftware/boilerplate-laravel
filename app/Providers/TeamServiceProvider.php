<?php

namespace App\Providers;

use App\Models\Addr;
use App\Models\BatchData;
use App\Models\Chan;
use App\Models\Family;
use App\Models\FamilyEvent;
use App\Models\FamilySlgs;
use App\Models\Person;
use App\Models\PersonAlia;
use App\Models\PersonAsso;
use App\Models\PersonEvent;
use App\Models\Subm;
use Illuminate\Support\ServiceProvider;

class TeamServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(\FamilyTree365\LaravelGedcom\Utils\BatchData::class, BatchData::class);
        $this->app->bind(\FamilyTree365\LaravelGedcom\Models\Family::class, Family::class);
        $this->app->bind(\FamilyTree365\LaravelGedcom\Models\FamilyEvent::class, FamilyEvent::class);
        $this->app->bind(\FamilyTree365\LaravelGedcom\Models\FamilySlgs::class, FamilySlgs::class);
        $this->app->bind(\FamilyTree365\LaravelGedcom\Models\Person::class, Person::class);
        $this->app->bind(\FamilyTree365\LaravelGedcom\Models\PersonAsso::class, PersonAsso::class);
        $this->app->bind(\FamilyTree365\LaravelGedcom\Models\PersonAlia::class, PersonAlia::class);
        $this->app->bind(\FamilyTree365\LaravelGedcom\Models\PersonEvent::class, PersonEvent::class);
        $this->app->bind(\FamilyTree365\LaravelGedcom\Models\Addr::class, Addr::class);
        $this->app->bind(\FamilyTree365\LaravelGedcom\Models\Chan::class, Chan::class);
        $this->app->bind(\FamilyTree365\LaravelGedcom\Models\Subm::class, Subm::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
