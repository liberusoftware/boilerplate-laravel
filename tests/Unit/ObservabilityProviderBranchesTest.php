<?php

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Liberu\Foundation\Observability\Contracts\ObservabilityActor;
use Liberu\Foundation\Observability\Providers\HorizonDashboardServiceProvider;
use Liberu\Foundation\Observability\Providers\TelescopeDashboardServiceProvider;

class CoverageObservabilityUser extends User implements ObservabilityActor
{
    public function isAdmin(): bool
    {
        return true;
    }

    public function hasRole(string $role): bool
    {
        return false;
    }
}

it('filters Telescope entries by every retained production signal', function (string $method) {
    Telescope::$filterUsing = [];
    (new TelescopeDashboardServiceProvider(app()))->register();
    $filter = Telescope::$filterUsing[array_key_last(Telescope::$filterUsing)];
    $entry = Mockery::mock(IncomingEntry::class);
    foreach (['isReportableException', 'isFailedRequest', 'isFailedJob', 'isScheduledTask', 'hasMonitoredTag'] as $candidate) {
        $entry->shouldReceive($candidate)->andReturn($candidate === $method);
    }

    expect($filter($entry))->toBeTrue();
})->with(['isReportableException', 'isFailedRequest', 'isFailedJob', 'isScheduledTask', 'hasMonitoredTag']);

it('registers Telescope and Horizon authorization gates', function () {
    $telescope = new class(app()) extends TelescopeDashboardServiceProvider
    {
        public function defineGate(): void
        {
            $this->gate();
        }
    };
    $horizon = new class(app()) extends HorizonDashboardServiceProvider
    {
        public function defineGate(): void
        {
            $this->gate();
        }
    };
    $telescope->defineGate();
    $horizon->defineGate();
    $admin = new CoverageObservabilityUser();

    expect(Gate::forUser($admin)->allows('viewTelescope'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewHorizon'))->toBeTrue()
        ->and(Gate::forUser(null)->allows('viewHorizon'))->toBeFalse();
});

it('skips Telescope request redaction in the local environment', function () {
    $original = app()->environment();
    app()->detectEnvironment(fn () => 'local');
    try {
        $provider = new class(app()) extends TelescopeDashboardServiceProvider
        {
            public function hideDetails(): void
            {
                $this->hideSensitiveRequestDetails();
            }
        };
        $provider->hideDetails();
        expect(app()->environment('local'))->toBeTrue();
    } finally {
        app()->detectEnvironment(fn () => $original);
    }
});
