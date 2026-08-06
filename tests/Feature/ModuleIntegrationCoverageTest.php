<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\InvalidStateException;
use Liberu\Foundation\ApiAccess\Support\IdempotencyStore;
use Liberu\Foundation\ApplicationCore\Health\ReadinessRegistry;
use Liberu\Foundation\ApplicationCore\Http\Controllers\ReadinessController;
use Liberu\Foundation\Audit\Support\AuditContext;
use Liberu\Foundation\Audit\Support\DatabaseAuditRecorder;
use Liberu\Foundation\Currency\Services\CurrencyPreferenceResolver;
use Liberu\Foundation\FeatureFlags\Support\FlagEvaluator;
use Liberu\Foundation\Identity\Contracts\InvitationValidator;
use Liberu\Foundation\Identity\Contracts\RegistrationPolicy;
use Liberu\Foundation\Identity\Socialstream\Actions\HandleInvalidState;
use Liberu\Foundation\Identity\Socialstream\Actions\SetUserPassword;
use Liberu\Foundation\Identity\Socialstream\Actions\UpdateConnectedAccount;
use Liberu\Foundation\Identity\Socialstream\Contracts\ConnectedAccountOwner;
use Liberu\Foundation\Identity\Socialstream\Models\ConnectedAccount as FoundationConnectedAccount;
use Liberu\Foundation\Identity\Socialstream\Policies\ConnectedAccountPolicy;
use Liberu\Foundation\Identity\Support\IdentifierNormalizer;
use Liberu\Foundation\JetstreamBridge\Actions\Fortify\CreateNewUser;
use Liberu\Foundation\JetstreamBridge\Actions\Fortify\ResetUserPassword;
use Liberu\Foundation\JetstreamBridge\Actions\Fortify\UpdateUserPassword;
use Liberu\Foundation\JetstreamBridge\Actions\Fortify\UpdateUserProfileInformation;
use Liberu\Foundation\Organizations\Actions\AcceptInvitation;
use Liberu\Foundation\Organizations\Actions\InviteMember;
use Liberu\Foundation\Organizations\Actions\TransferOwnership;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\Organizations\Services\CurrentTeamResolver;
use Liberu\Foundation\Profiles\Actions\UpdateProfile;
use Liberu\Foundation\Profiles\Data\ProfileUpdate;
use Liberu\Foundation\RolesPermissions\Policies\RolePolicy;
use Liberu\Foundation\RolesPermissions\Services\BreakGlass;
use Liberu\Foundation\Search\Registry\IndexableRegistry;
use Liberu\Foundation\Search\Services\LocalSearchIndexer;
use Liberu\Foundation\Sessions\Queries\SessionReader;
use Liberu\Foundation\Settings\Contracts\SettingDefinition;
use Liberu\Foundation\Settings\Services\ScopedSettings;
use Liberu\Foundation\Theme\Cache\ThemeCache;
use Liberu\Foundation\Theme\Exceptions\InvalidTheme;
use Liberu\Foundation\TwoFactor\TrustedDevices\TrustedDeviceManager;
use Spatie\Permission\Models\Role;

it('covers readiness responses and audit value objects', function () {
    config()->set('application-core.release', 'test-release');
    $response = (new ReadinessController())(new ReadinessRegistry());
    $context = new AuditContext(1, 'user', 'tenant', 'request', 'correlation', 'reason');

    expect($response->status())->toBe(200)
        ->and($response->getData(true))->toBe(['status' => 'ready', 'checks' => [], 'release' => 'test-release'])
        ->and($context->actorId)->toBe(1)
        ->and($context->reason)->toBe('reason');
});

it('records a hash-chained database audit event', function () {
    config()->set('audit.retention_days', 30);
    (new DatabaseAuditRecorder())->record('updated', 'profile', 7, ['name' => 'Before'], ['name' => 'After'], new AuditContext(1, 'user', 'tenant', 'request', 'correlation'));

    $record = DB::table('activity_log')->first();
    expect($record->description)->toBe('updated')
        ->and($record->record_hash)->toHaveLength(64)
        ->and($record->tenant_ref)->toBe('tenant');
});

it('evaluates every feature flag constraint', function () {
    $flags = new FlagEvaluator();
    expect($flags->enabled('off', [], 'testing'))->toBeFalse()
        ->and($flags->enabled('expired', ['enabled' => true, 'expires_at' => '2000-01-01'], 'testing'))->toBeFalse()
        ->and($flags->enabled('environment', ['enabled' => true, 'environments' => ['production']], 'testing'))->toBeFalse()
        ->and($flags->enabled('tenant', ['enabled' => true, 'tenants' => [7], 'percentage' => 0], 'testing', 7))->toBeTrue()
        ->and($flags->enabled('actor', ['enabled' => true, 'actors' => [9], 'percentage' => 0], 'testing', null, 9))->toBeTrue()
        ->and($flags->enabled('all', ['enabled' => true, 'percentage' => 100], 'testing'))->toBeTrue()
        ->and($flags->enabled('none', ['enabled' => true, 'percentage' => -10], 'testing'))->toBeFalse();
});

it('stores and replays idempotent API requests', function () {
    $store = new IdempotencyStore();
    expect($store->begin('actor', 'key', 'body'))->toBeNull();
    $existing = $store->begin('actor', 'key', 'body');
    expect($existing->request_hash)->toBe(hash('sha256', 'body'));
    expect(fn () => $store->begin('actor', 'key', 'different'))->toThrow(RuntimeException::class);
    $store->complete('actor', 'key', 201, 'created');
    expect(DB::table('api_idempotency_keys')->value('response_status'))->toBe(201);
});

it('resolves currency preferences from ordered scopes', function () {
    DB::table('currency_preferences')->insert(['scope_type' => 'team', 'scope_id' => '4', 'currency' => 'gbp', 'created_at' => now(), 'updated_at' => now()]);
    $resolver = new CurrencyPreferenceResolver();

    expect($resolver->resolve(['user' => null, 'team' => 4], 'usd'))->toBe('GBP')
        ->and($resolver->resolve(['team' => 99], 'eur'))->toBe('EUR');
});

it('writes resolves and validates scoped settings', function () {
    $plain = new class() implements SettingDefinition
    {
        public function key(): string
        {
            return 'plain';
        }

        public function validate(mixed $value): bool
        {
            return is_string($value);
        }

        public function secret(): bool
        {
            return false;
        }
    };
    $secret = new class() implements SettingDefinition
    {
        public function key(): string
        {
            return 'secret';
        }

        public function validate(mixed $value): bool
        {
            return is_string($value);
        }

        public function secret(): bool
        {
            return true;
        }
    };
    $settings = new ScopedSettings();
    $settings->put($plain, 'team', '1', 'visible');
    $settings->put($secret, 'team', '1', 'hidden');

    expect($settings->resolve('plain', ['user' => null, 'team' => 1]))->toBe('visible')
        ->and($settings->resolve('secret', ['team' => 1]))->toBe('hidden')
        ->and($settings->resolve('missing', ['team' => 1], 'fallback'))->toBe('fallback');
    expect(fn () => $settings->put($plain, 'team', '1', 3))->toThrow(InvalidArgumentException::class);
});

it('reads redacts and revokes sessions', function () {
    DB::table('sessions')->insert([
        ['id' => 'current', 'user_id' => 5, 'ip_address' => '192.168.1.42', 'user_agent' => 'browser', 'payload' => '', 'last_activity' => 2],
        ['id' => 'other', 'user_id' => 5, 'ip_address' => '2001:db8::1234', 'user_agent' => 'phone', 'payload' => '', 'last_activity' => 1],
    ]);
    $reader = new SessionReader();
    $sessions = $reader->forActor(5, 'current');

    expect($sessions[0]->is_current)->toBeTrue()
        ->and($sessions[0]->ip_address)->toBe('192.168.1.0')
        ->and($sessions[1]->ip_address)->toBe('2001:db8::0')
        ->and($reader->revoke(5, 'current', 'current'))->toBeFalse()
        ->and($reader->revoke(5, 'other', 'current'))->toBeTrue();
    DB::table('sessions')->insert(['id' => 'third', 'user_id' => 5, 'ip_address' => null, 'user_agent' => null, 'payload' => '', 'last_activity' => 3]);
    expect($reader->forActor(5)->first()->ip_address)->toBeNull()
        ->and($reader->revokeOthers(5, 'current'))->toBe(1);
});

it('issues validates and revokes trusted devices', function () {
    $manager = new TrustedDeviceManager();
    $credential = $manager->issue(7, 'laptop');

    expect($manager->valid(7, $credential))->toBeTrue()
        ->and($manager->valid(7, 'bad.value'))->toBeFalse()
        ->and($manager->revokeAll(7))->toBe(1)
        ->and($manager->valid(7, $credential))->toBeFalse();
});

it('grants and checks emergency authorization', function () {
    $service = new BreakGlass();
    expect(fn () => $service->grant(1, 'admin', '', new DateTimeImmutable('+1 hour'), true))->toThrow(RuntimeException::class);
    $id = $service->grant(1, 'admin', 'incident', new DateTimeImmutable('+1 hour'), true);
    expect($id)->toBeInt()->and($service->active(1, 'admin'))->toBeTrue();
});

it('delegates every role policy permission check', function () {
    $user = Mockery::mock(Illuminate\Foundation\Auth\User::class);
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.view-any')->andReturnTrue();
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.view')->andReturnTrue();
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.create')->andReturnTrue();
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.update')->andReturnTrue();
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.delete')->andReturnTrue();
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.delete-any')->andReturnTrue();
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.restore')->andReturnTrue();
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.force-delete')->andReturnTrue();
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.force-delete-any')->andReturnTrue();
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.restore-any')->andReturnTrue();
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.replicate')->andReturnTrue();
    $user->shouldReceive('can')->once()->with('roles-permissions.roles.reorder')->andReturnTrue();
    $role = Mockery::mock(Role::class);
    $policy = new RolePolicy();

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->view($user, $role))->toBeTrue()
        ->and($policy->create($user))->toBeTrue()
        ->and($policy->update($user, $role))->toBeTrue()
        ->and($policy->delete($user, $role))->toBeTrue()
        ->and($policy->deleteAny($user))->toBeTrue()
        ->and($policy->restore($user, $role))->toBeTrue()
        ->and($policy->forceDelete($user, $role))->toBeTrue()
        ->and($policy->forceDeleteAny($user))->toBeTrue()
        ->and($policy->restoreAny($user))->toBeTrue()
        ->and($policy->replicate($user, $role))->toBeTrue()
        ->and($policy->reorder($user))->toBeTrue();
});

it('updates model profiles and exercises no-op local indexing', function () {
    $profile = new class() extends Model
    {
        protected $table = 'users';

        protected $guarded = [];
    };
    $profile->forceFill(['name' => 'Before', 'email' => 'coverage@example.test', 'password' => 'secret'])->save();
    $updated = (new UpdateProfile())->handle($profile, new ProfileUpdate(' After ', 'en', 'UTC', 'dark'));
    $indexer = new LocalSearchIndexer();
    $indexer->index('user', $updated);
    $indexer->remove('user', $updated->getKey());
    $indexer->flush('user');

    expect($updated->name)->toBe('After')->and($updated->theme_preference)->toBe('dark');
});

it('registers indexables and manages serialized theme caches', function () {
    $registry = new IndexableRegistry();
    $registry->register('users', 'User');
    expect($registry->all())->toBe(['users' => 'User']);
    expect(fn () => $registry->register('users', 'Other'))->toThrow(InvalidArgumentException::class);

    $path = sys_get_temp_dir().'/theme-cache-'.bin2hex(random_bytes(5));
    $cache = new ThemeCache();
    $cache->write(['default' => ['name' => 'Default']], $path);
    expect($cache->load($path))->toBe(['default' => ['name' => 'Default']]);
    file_put_contents($path, serialize('invalid'));
    expect(fn () => $cache->load($path))->toThrow(InvalidTheme::class);
    $cache->clear($path);
    $cache->clear($path);
});

it('creates users under registration policy and validates rejected registration', function () {
    $open = Mockery::mock(RegistrationPolicy::class);
    $open->shouldReceive('permitsSelfRegistration')->andReturnTrue();
    $open->shouldReceive('requiresInvitation')->andReturnFalse();
    $invitations = Mockery::mock(InvitationValidator::class);
    $action = new CreateNewUser($open, $invitations, new IdentifierNormalizer());
    $user = $action->create(['name' => 'Coverage User', 'email' => ' COVERAGE@EXAMPLE.TEST ', 'password' => 'A-strong-password-123!', 'password_confirmation' => 'A-strong-password-123!']);
    expect($user->email)->toBe('coverage@example.test');

    $closed = Mockery::mock(RegistrationPolicy::class);
    $closed->shouldReceive('permitsSelfRegistration')->andReturnFalse();
    expect(fn () => (new CreateNewUser($closed, $invitations, new IdentifierNormalizer()))->create(['email' => 'x@example.test']))
        ->toThrow(ValidationException::class);
});

it('resets updates and sets account passwords', function () {
    $user = User::factory()->create(['password' => bcrypt('old-password')]);
    (new ResetUserPassword())->reset($user, ['password' => 'A-new-password-123!', 'password_confirmation' => 'A-new-password-123!']);
    expect(password_verify('A-new-password-123!', $user->fresh()->password))->toBeTrue();

    auth()->login($user);
    (new UpdateUserPassword())->update($user, ['current_password' => 'A-new-password-123!', 'password' => 'Another-password-123!', 'password_confirmation' => 'Another-password-123!']);
    expect(password_verify('Another-password-123!', $user->fresh()->password))->toBeTrue();

    (new SetUserPassword())->set($user, ['password' => 'Social-password-123!', 'password_confirmation' => 'Social-password-123!']);
    expect(password_verify('Social-password-123!', $user->fresh()->password))->toBeTrue();
});

it('updates ordinary profile information', function () {
    $user = User::factory()->create(['email' => 'before@example.test']);
    (new UpdateUserProfileInformation(new IdentifierNormalizer()))->update($user, ['name' => 'After', 'email' => ' AFTER@EXAMPLE.TEST ']);
    expect($user->fresh()->only(['name', 'email']))->toBe(['name' => 'After', 'email' => 'after@example.test']);
});

it('rethrows invalid social provider state and authorizes connected account ownership', function () {
    $exception = new InvalidStateException();
    expect(fn () => (new HandleInvalidState())->handle($exception))->toThrow($exception::class);

    $account = new FoundationConnectedAccount();
    $owner = Mockery::mock(ConnectedAccountOwner::class);
    $owner->shouldReceive('ownsConnectedAccount')->times(3)->with($account)->andReturnTrue();
    $policy = new ConnectedAccountPolicy();
    expect($policy->viewAny($owner))->toBeTrue()
        ->and($policy->create($owner))->toBeTrue()
        ->and($policy->view($owner, $account))->toBeTrue()
        ->and($policy->update($owner, $account))->toBeTrue()
        ->and($policy->delete($owner, $account))->toBeTrue();
});

it('updates a connected account from a social provider profile', function () {
    Gate::before(fn () => true);
    $owner = User::factory()->create();
    $account = FoundationConnectedAccount::factory()->for($owner)->create();
    $providerUser = Mockery::mock(Laravel\Socialite\Contracts\User::class);
    $providerUser->shouldReceive('getId')->andReturn('provider-123');
    $providerUser->shouldReceive('getName')->andReturn('Coverage Person');
    $providerUser->shouldReceive('getNickname')->andReturn('coverage');
    $providerUser->shouldReceive('getEmail')->andReturn('social@example.test');
    $providerUser->shouldReceive('getAvatar')->andReturn('https://example.test/avatar.png');
    $providerUser->token = 'access-token';
    $providerUser->tokenSecret = 'token-secret';
    $providerUser->refreshToken = 'refresh-token';
    $providerUser->expiresIn = 3600;

    $updated = (new UpdateConnectedAccount())
        ->update($owner, $account, 'GITHUB', $providerUser);

    expect($updated->fresh()->only(['provider', 'provider_id', 'name', 'nickname', 'email', 'avatar_path']))
        ->toBe([
            'provider' => 'github', 'provider_id' => 'provider-123', 'name' => 'Coverage Person',
            'nickname' => 'coverage', 'email' => 'social@example.test',
            'avatar_path' => 'https://example.test/avatar.png',
        ]);
});

it('runs module theme and foundation operational commands', function () {
    $moduleCache = sys_get_temp_dir().'/module-registry-'.bin2hex(random_bytes(4));
    $themeCache = sys_get_temp_dir().'/theme-registry-'.bin2hex(random_bytes(4));
    config()->set('modules.cache_path', $moduleCache);
    config()->set('theme.cache_path', $themeCache);

    expect(Artisan::call('module:list'))->toBe(0)
        ->and(Artisan::call('module:status', ['name' => 'missing']))->toBe(1)
        ->and(Artisan::call('module:status', ['name' => 'application']))->toBe(0)
        ->and(Artisan::call('module:validate'))->toBe(0)
        ->and(Artisan::call('foundation:doctor'))->toBe(0)
        ->and(Artisan::call('module:cache'))->toBe(0)
        ->and(is_file($moduleCache))->toBeTrue()
        ->and(Artisan::call('module:clear'))->toBe(0)
        ->and(is_file($moduleCache))->toBeFalse()
        ->and(Artisan::call('theme:validate'))->toBe(0)
        ->and(Artisan::call('theme:cache'))->toBe(0)
        ->and(is_file($themeCache))->toBeTrue()
        ->and(Artisan::call('theme:clear'))->toBe(0)
        ->and(is_file($themeCache))->toBeFalse()
        ->and(Artisan::call('search:reindex', ['type' => 'missing']))->toBe(1);
});

it('invites accepts resolves and transfers team membership', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::forceCreate(['user_id' => $owner->id, 'name' => 'Coverage', 'personal_team' => false, 'status' => 'active']);
    $token = (new InviteMember())->handle($team->id, ' MEMBER@EXAMPLE.TEST ', 'member', $owner->id);
    (new AcceptInvitation())->handle($token, $member, 'member@example.test');

    expect((new CurrentTeamResolver())->resolve($member, null))->toBeNull()
        ->and((new CurrentTeamResolver())->resolve($member, $team->id)?->id)->toBe($team->id);
    (new TransferOwnership())->handle($team, $owner->id, $member->id, true);
    expect((int) $team->fresh()->user_id)->toBe($member->id);
    expect(fn () => (new TransferOwnership())->handle($team, $owner->id, 999, false))->toThrow(RuntimeException::class);

    expect(fn () => (new AcceptInvitation())->handle('bad-token', $member, 'member@example.test'))->toThrow(RuntimeException::class);
});
