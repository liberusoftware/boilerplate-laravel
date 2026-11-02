<?php

declare(strict_types=1);

namespace Liberu\Foundation\Tenancy\Tests;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\User;
use Liberu\Foundation\Tenancy\Jobs\Middleware\RestoreTenantContext;
use Liberu\Foundation\Tenancy\TenancyServiceProvider;
use Liberu\Foundation\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TenantContextTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application(sys_get_temp_dir());
        (new TenancyServiceProvider($this->app))->register();
    }

    protected function tearDown(): void
    {
        $this->app->flush();
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_context_is_reset_between_worker_lifecycles(): void
    {
        TenantContext::set(42);
        $this->assertSame(42, TenantContext::currentId());

        $this->app->forgetScopedInstances();

        $this->assertNull(TenantContext::currentId());
    }

    public function test_failed_job_clears_its_tenant(): void
    {
        $job = (object) ['tenantId' => 42];

        try {
            (new RestoreTenantContext())->handle($job, function (): never {
                $this->assertSame(42, TenantContext::currentId());
                throw new RuntimeException('Failed job');
            });
            $this->fail('The job exception must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Failed job', $exception->getMessage());
        }

        $this->assertNull(TenantContext::currentId());
    }

    public function test_job_restores_the_captured_team(): void
    {
        $job = (object) ['tenantId' => 23];
        $seen = (new RestoreTenantContext())->handle($job, fn (): ?int => TenantContext::currentId());

        $this->assertSame(23, $seen);
        $this->assertNull(TenantContext::currentId());
    }

    public function test_user_context_uses_the_current_team_relationship(): void
    {
        $user = new class() extends User
        {
            public function currentTeam(): BelongsTo
            {
                return $this->belongsTo(Model::class);
            }
        };
        $team = new class() extends Model {};
        $team->setAttribute('id', 23);
        $user->setRelation('currentTeam', $team);

        $this->assertSame(23, TenantContext::forUser($user));
    }

    public function test_user_without_team_support_has_no_context(): void
    {
        $this->assertNull(TenantContext::forUser(new User()));
        $this->assertNull(TenantContext::forUser(null));
    }
}
