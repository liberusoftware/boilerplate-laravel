<?php

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Liberu\Foundation\ApplicationCore\Http\Middleware\SecurityHeaders;
use Liberu\Foundation\Currency\Services\MoneyFormatter;
use Liberu\Foundation\Currency\ValueObjects\Currency;
use Liberu\Foundation\Currency\ValueObjects\Money;
use Liberu\Foundation\DeveloperExperience\Support\EnvironmentDoctor;
use Liberu\Foundation\Filament\FoundationAccountPlugin;
use Liberu\Foundation\Filament\Pages\AccountSecurity;
use Liberu\Foundation\Filament\Pages\FoundationOperations;
use Liberu\Foundation\Identity\Listeners\EmitAuthenticationEvent;
use Liberu\Foundation\Localization\Context\LocaleContext;
use Liberu\Foundation\Localization\Context\LocaleResolver;
use Liberu\Foundation\Localization\MyMemory\TranslationService;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Liberu\Foundation\Organizations\Contracts\OrganizationActor;
use Liberu\Foundation\Organizations\Models\Organization;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\Organizations\Models\TeamInvitation;
use Liberu\Foundation\Organizations\Policies\TeamPolicy;
use Liberu\Foundation\Sessions\Queries\SessionReader;
use Liberu\Messaging\Core\Contracts\Messaging;
use Liberu\Messaging\Filament\MessagingFilamentPlugin;
use Liberu\Messaging\Filament\Pages\Inbox;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

it('covers HTTPS security locale and organization model branches', function () {
    $request = Request::create('https://example.test/secure');
    $response = (new SecurityHeaders())->handle($request, fn () => new Response());
    expect($response->headers->get('Strict-Transport-Security'))->toContain('max-age')
        ->and((new LocaleContext('en', 'UTC', 'ltr'))->payload())->toBe(['locale' => 'en', 'timezone' => 'UTC']);

    $organization = new Organization();
    $invitation = new TeamInvitation();
    expect($organization->getCasts()['archived_at'])->toBe('datetime')
        ->and($organization->teams()->getRelated())->toBeInstanceOf(Team::class)
        ->and($invitation->getCasts())->toMatchArray(['expires_at' => 'datetime', 'accepted_at' => 'datetime', 'revoked_at' => 'datetime'])
        ->and($invitation->team()->getRelated())->toBeInstanceOf(Team::class);
});

it('mounts account operations and inbox pages through their contracts', function () {
    $user = (new User())->forceFill(['id' => 1, 'name' => 'Coverage', 'email' => 'coverage@example.test']);
    $this->actingAs($user);
    $reader = new SessionReader();
    foreach (['one', 'two'] as $id) {
        DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'Coverage', 'payload' => '', 'last_activity' => time(),
        ]);
    }
    $security = new AccountSecurity();
    $security->mount($reader);
    $security->revoke('one', $reader);

    $operations = new FoundationOperations();
    $operations->mount(new ModuleRegistry([]));
    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldReceive('conversations')->once()->with($user->id)->andReturn([['id' => 1]]);
    $inbox = new Inbox();
    $inbox->mount($messaging);

    expect(AccountSecurity::canAccess())->toBeTrue()
        ->and($security->sessions)->toHaveCount(1)
        ->and($operations->modules)->toBe([])
        ->and(Inbox::canAccess())->toBeTrue()
        ->and($inbox->conversations)->toBe([['id' => 1]]);
});

it('boots the Filament plugins without additional panel configuration', function () {
    $panel = Panel::make();

    expect(FoundationAccountPlugin::make()->boot($panel))->toBeNull()
        ->and(MessagingFilamentPlugin::make()->boot($panel))->toBeNull();
});

it('covers unconditional team policy permissions', function () {
    $actor = Mockery::mock(OrganizationActor::class);
    $policy = new TeamPolicy();
    expect($policy->viewAny($actor))->toBeTrue()
        ->and($policy->create($actor))->toBeTrue();
});

it('preserves nested and non-string values in translation batches', function () {
    expect((new TranslationService())->translateBatch(['nested' => ['text' => 'Hello'], 'number' => 42], 'en'))
        ->toBe(['nested' => ['text' => 'Hello'], 'number' => 42]);
});

it('ignores unknown authentication events and normalizes invalid timezones', function () {
    (new EmitAuthenticationEvent())->handle(new stdClass());
    config()->set('localization.locales', ['en' => 'English']);
    config()->set('localization.site_timezone', 'Invalid/Timezone');
    $context = (new LocaleResolver())->resolve(Request::create('/'));

    expect($context->timezone)->toBe('UTC');
});

it('uses a remote profile photo URL directly', function () {
    $user = (new User())->forceFill(['profile_photo_path' => 'https://cdn.example.test/photo.jpg']);
    expect($user->profile_photo_url)->toBe('https://cdn.example.test/photo.jpg');
});

it('falls back to deterministic money formatting when intl formatting fails', function () {
    $formatter = new MoneyFormatter(fn () => new class()
    {
        public function formatCurrency(float $amount, string $currency): false
        {
            return false;
        }
    });
    $money = new Money(1234, new Currency('USD', 2, '$'));

    expect($formatter->format($money, 'en_US'))->toBe('USD 12.34');
});

it('reports missing extensions and unwritable runtime paths', function () {
    $errors = (new EnvironmentDoctor())->inspect(
        ['definitely_missing_extension'],
        ['/definitely/missing/runtime/path'],
    );

    expect($errors)->toHaveCount(2)
        ->and($errors[0])->toContain('Missing PHP extension')
        ->and($errors[1])->toContain('Not writable');
});
