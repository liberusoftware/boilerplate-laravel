<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Liberu\Analytics\Core\Support\EventSanitizer;
use Liberu\Foundation\ActivityComments\Support\Visibility;
use Liberu\Foundation\ApiAccess\Support\TokenPolicy;
use Liberu\Foundation\ApplicationCore\Health\ReadinessCheck;
use Liberu\Foundation\ApplicationCore\Health\ReadinessRegistry;
use Liberu\Foundation\ApplicationCore\Support\EnvironmentValidator;
use Liberu\Foundation\ApplicationCore\Support\SystemClock;
use Liberu\Foundation\ApplicationCore\Support\UuidIdentifierFactory;
use Liberu\Foundation\Currency\Enums\CurrencyRole;
use Liberu\Foundation\Currency\Exceptions\UnknownCurrency;
use Liberu\Foundation\Currency\Services\CurrencyContext;
use Liberu\Foundation\Currency\Services\CurrencyRegistry;
use Liberu\Foundation\Currency\Services\MoneyFormatter;
use Liberu\Foundation\Currency\ValueObjects\Currency;
use Liberu\Foundation\Currency\ValueObjects\ExchangeRate;
use Liberu\Foundation\Currency\ValueObjects\Money;
use Liberu\Foundation\Files\Support\RejectingMalwareScanner;
use Liberu\Foundation\Files\Support\UploadPolicy;
use Liberu\Foundation\Identity\Support\ConfiguredRegistrationPolicy;
use Liberu\Foundation\Identity\Support\IdentifierNormalizer;
use Liberu\Foundation\Identity\Support\RejectingInvitationValidator;
use Liberu\Foundation\ImportExport\Data\TransferSchema;
use Liberu\Foundation\ImportExport\Support\RowValidator;
use Liberu\Foundation\Integrations\Contracts\IntegrationAdapter;
use Liberu\Foundation\Integrations\Support\CredentialVault;
use Liberu\Foundation\Integrations\Support\IntegrationRegistry;
use Liberu\Foundation\Localization\Formatting\LocaleFormatter;
use Liberu\Foundation\Notifications\Support\DeliveryRetry;
use Liberu\Foundation\Notifications\Support\NotificationPolicy;
use Liberu\Foundation\Observability\Support\NullMetrics;
use Liberu\Foundation\Observability\Support\Redactor;
use Liberu\Foundation\Observability\Support\SloRegistry;
use Liberu\Foundation\RolesPermissions\Registry\PermissionRegistry;
use Liberu\Foundation\RolesPermissions\Services\SeparationOfDuty;
use Liberu\Foundation\SchedulerQueues\Support\JobPolicy;
use Liberu\Foundation\TwoFactor\Enforcement\TwoFactorPolicy;
use Liberu\Foundation\TwoFactor\Recovery\RecoveryCodeHasher;
use Liberu\Foundation\Webhooks\Support\RetrySchedule;
use Liberu\Foundation\Webhooks\Support\SigningSecretVault;
use Liberu\Foundation\Webhooks\Support\WebhookSigner;

it('covers activity visibility decisions', function () {
    expect(Visibility::Public->visible(false, false, false))->toBeTrue()
        ->and(Visibility::Members->visible(true, false, false))->toBeTrue()
        ->and(Visibility::Members->visible(false, true, false))->toBeTrue()
        ->and(Visibility::Members->visible(false, false, false))->toBeFalse()
        ->and(Visibility::Internal->visible(false, true, false))->toBeTrue()
        ->and(Visibility::Internal->visible(true, false, false))->toBeFalse()
        ->and(Visibility::Private->visible(false, false, true))->toBeTrue()
        ->and(Visibility::Private->visible(false, true, false))->toBeTrue()
        ->and(Visibility::Private->visible(false, false, false))->toBeFalse();
});

it('sanitizes and pseudonymizes analytics properties', function () {
    $sanitizer = new EventSanitizer();

    expect($sanitizer->sanitize(['safe' => 1, 'secret' => 2], ['safe']))->toBe(['safe' => 1])
        ->and($sanitizer->pseudonymize(null, 'salt'))->toBeNull()
        ->and($sanitizer->pseudonymize(' User@Example.COM ', 'salt'))
        ->toBe(hash_hmac('sha256', 'user@example.com', 'salt'));
});

it('constrains API token scopes and expiry', function () {
    config()->set('api-access.maximum_token_days', 10);
    $policy = new TokenPolicy();
    $requested = new DateTimeImmutable('+1 day');

    expect($policy->scopes(['read', 'read'], ['read', 'write']))->toBe(['read'])
        ->and($policy->expiresAt($requested))->toBe($requested)
        ->and($policy->expiresAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($policy->expiresAt(new DateTimeImmutable('+20 days')))->toBeLessThan(new DateTimeImmutable('+11 days'));
    expect(fn () => $policy->scopes(['admin'], ['read']))->toThrow(InvalidArgumentException::class);
});

it('registers readiness checks and rejects duplicate names', function () {
    $passing = new class() implements ReadinessCheck
    {
        public function name(): string
        {
            return 'database';
        }

        public function ready(): bool
        {
            return true;
        }
    };
    $failing = new class() implements ReadinessCheck
    {
        public function name(): string
        {
            return 'queue';
        }

        public function ready(): bool
        {
            return false;
        }
    };
    $registry = new ReadinessRegistry();
    $registry->register($passing);

    expect($registry->report())->toBe(['database' => true])
        ->and($registry->ready())->toBeTrue();

    $registry->register($failing);
    expect($registry->ready())->toBeFalse();
    expect(fn () => $registry->register($passing))->toThrow(InvalidArgumentException::class);
});

it('provides UTC clocks and UUID identifiers', function () {
    expect((new SystemClock())->now()->getTimezone()->getName())->toBe('UTC')
        ->and((new UuidIdentifierFactory())->make())->toMatch('/^[0-9a-f-]{36}$/');
});

it('validates required and production configuration', function () {
    config()->set('application-core.required_configuration', ['covered.present']);
    config()->set('covered.present', true);
    (new EnvironmentValidator())->validate();

    config()->set('application-core.required_configuration', ['covered.missing']);
    expect(fn () => (new EnvironmentValidator())->validate())->toThrow(RuntimeException::class, 'covered.missing');

    config()->set('application-core.required_configuration', []);
    config()->set('app.debug', true);
    $this->app['env'] = 'production';
    expect(fn () => (new EnvironmentValidator())->validate())->toThrow(RuntimeException::class, 'APP_DEBUG');
    $this->app['env'] = 'testing';
});

it('validates currencies, money arithmetic, formatting and rates', function () {
    $gbp = new Currency('gbp', 2, '£');
    $jpy = new Currency('JPY', 0, '¥');
    $money = new Money(123, $gbp);

    expect($gbp->code)->toBe('GBP')
        ->and($money->add(new Money(77, $gbp))->decimal())->toBe('2.00')
        ->and($money->subtract(new Money(23, $gbp))->decimal())->toBe('1.00')
        ->and($money->equals(new Money(123, $gbp)))->toBeTrue()
        ->and($money->equals(new Money(124, $gbp)))->toBeFalse()
        ->and((new Money(-5, $gbp))->decimal())->toBe('-0.05')
        ->and((new Money(5, $jpy))->decimal())->toBe('5')
        ->and((new MoneyFormatter())->format($money, 'en_GB'))->toContain('1.23');

    expect(fn () => new Currency('US', 2, '?'))->toThrow(UnknownCurrency::class);
    expect(fn () => new Currency('GBP', 7, '£'))->toThrow(UnknownCurrency::class);

    $rate = new ExchangeRate($gbp, $jpy, '190.5', 'test', 'spot', new DateTimeImmutable('@100'), 4);
    expect($rate->isStale(new DateTimeImmutable('@200'), 99))->toBeTrue()
        ->and($rate->isStale(new DateTimeImmutable('@200'), 100))->toBeFalse();
    expect(fn () => new ExchangeRate($gbp, $gbp, '1', 'test', 'spot', new DateTimeImmutable(), 4))
        ->toThrow(InvalidArgumentException::class);
});

it('resolves registered and contextual currencies', function () {
    $registry = new CurrencyRegistry(['GBP' => ['minor_units' => 2, 'symbol' => '£']]);
    $gbp = $registry->get('gbp');
    $context = new CurrencyContext([CurrencyRole::Base->value => $gbp]);

    expect($context->for(CurrencyRole::Base))->toBe($gbp);
    expect(fn () => $registry->get('USD'))->toThrow(UnknownCurrency::class);
});

it('enforces upload policy and rejects unscanned files', function () {
    $policy = new UploadPolicy();
    $policy->assert('image/png', 10, ['image/png'], 20);

    expect((new RejectingMalwareScanner())->clean('/tmp/file'))->toBeFalse();
    expect(fn () => $policy->assert('image/png', 0, ['image/png'], 20))->toThrow(InvalidArgumentException::class, 'size');
    expect(fn () => $policy->assert('text/plain', 10, ['image/png'], 20))->toThrow(InvalidArgumentException::class, 'type');
});

it('covers identity registration and identifier defaults', function () {
    expect((new ConfiguredRegistrationPolicy('open'))->permitsSelfRegistration())->toBeTrue()
        ->and((new ConfiguredRegistrationPolicy('invitation'))->permitsSelfRegistration())->toBeTrue()
        ->and((new ConfiguredRegistrationPolicy('closed'))->permitsSelfRegistration())->toBeFalse()
        ->and((new ConfiguredRegistrationPolicy('invitation'))->requiresInvitation())->toBeTrue()
        ->and((new ConfiguredRegistrationPolicy('open'))->requiresInvitation())->toBeFalse()
        ->and((new IdentifierNormalizer())->email(' User@EXAMPLE.com '))->toBe('user@example.com')
        ->and((new RejectingInvitationValidator())->valid('user@example.com', 'token'))->toBeFalse();
});

it('validates transfer schemas and every supported row type', function () {
    expect(fn () => new TransferSchema('empty', '1', []))->toThrow(InvalidArgumentException::class);

    $schema = new TransferSchema('people', '1', [
        'name' => ['required' => true, 'type' => 'string'],
        'age' => ['type' => 'integer'],
        'score' => ['type' => 'number'],
        'active' => ['type' => 'boolean'],
        'joined' => ['type' => 'date'],
        'unknown' => ['type' => 'unsupported'],
    ]);
    $validator = new RowValidator();

    expect($validator->validate($schema, ['name' => 'Ada', 'age' => '42', 'score' => '1.5', 'active' => '1', 'joined' => '2026-01-01', 'unknown' => 'value']))
        ->toBe(['unknown' => ['type']])
        ->and($validator->validate($schema, ['name' => '', 'age' => 'no', 'score' => [], 'active' => 2, 'joined' => 'bad']))
        ->toMatchArray(['name' => ['required'], 'age' => ['type'], 'score' => ['type'], 'active' => ['type'], 'joined' => ['type']]);
});

it('encrypts credentials and manages integration adapters', function () {
    $vault = new CredentialVault();
    expect($vault->open($vault->seal(['token' => 'secret'])))->toBe(['token' => 'secret']);

    $adapter = new class() implements IntegrationAdapter
    {
        public function name(): string
        {
            return 'covered';
        }

        public function capabilities(): array
        {
            return ['test'];
        }

        public function test(array $credentials): bool
        {
            return $credentials !== [];
        }
    };
    $registry = new IntegrationRegistry();
    $registry->register($adapter);

    expect($registry->get('covered'))->toBe($adapter)
        ->and($registry->all())->toBe(['covered' => $adapter]);
    expect(fn () => $registry->register($adapter))->toThrow(InvalidArgumentException::class, 'Duplicate');
    expect(fn () => $registry->get('missing'))->toThrow(InvalidArgumentException::class, 'Unknown');
});

it('formats locale-aware values and lists', function () {
    $formatter = new LocaleFormatter();
    expect($formatter->date(new DateTimeImmutable('2026-08-01'), 'en_GB'))->toBeString()
        ->and($formatter->number(1234.5, 'en_GB'))->toBeString()
        ->and($formatter->list(['one'], 'en'))->toBe('one')
        ->and($formatter->list(['one', 'two'], 'en'))->toBe('one and two')
        ->and($formatter->list(['one', 'two'], 'fr'))->toBe('one two')
        ->and($formatter->list(['one', 'two', 'three'], 'en'))->toBe('one, two, three');
});

it('covers notification retry and quiet-hour policy', function () {
    $retry = new DeliveryRetry();
    $policy = new NotificationPolicy();

    expect($retry->delay(1))->toBe(60)
        ->and($retry->delay(20))->toBe(15360)
        ->and($retry->exhausted(7))->toBeFalse()
        ->and($retry->exhausted(8))->toBeTrue()
        ->and($policy->channels(['mail', 'sms'], ['mail']))->toBe(['mail'])
        ->and($policy->channels([], [], true))->toBe(['database'])
        ->and($policy->channels([], [], false))->toBe([])
        ->and($policy->isQuiet(new DateTimeImmutable('12:00'), null, '13:00'))->toBeFalse()
        ->and($policy->isQuiet(new DateTimeImmutable('12:00'), '09:00', '17:00'))->toBeTrue()
        ->and($policy->isQuiet(new DateTimeImmutable('18:00'), '09:00', '17:00'))->toBeFalse()
        ->and($policy->isQuiet(new DateTimeImmutable('23:00'), '22:00', '06:00'))->toBeTrue()
        ->and($policy->isQuiet(new DateTimeImmutable('12:00'), '22:00', '06:00'))->toBeFalse();
});

it('covers observability fallbacks, redaction and SLO registration', function () {
    $metrics = new NullMetrics();
    $metrics->increment('requests');
    $metrics->observe('latency', 1.2);

    config()->set('observability.sensitive_keys', ['token', 'password']);
    expect((new Redactor())->redact(['token_value' => 'secret', 'nested' => ['password' => 'secret'], 'safe' => 'yes']))
        ->toBe(['token_value' => '[REDACTED]', 'nested' => ['password' => '[REDACTED]'], 'safe' => 'yes']);

    $registry = new SloRegistry();
    $registry->register('availability', 0.999, '30d');
    expect($registry->all())->toBe(['availability' => ['target' => 0.999, 'window' => '30d']]);
    expect(fn () => $registry->register('availability', 0.9, '7d'))->toThrow(InvalidArgumentException::class);
    expect(fn () => (new SloRegistry())->register('invalid', 0, '7d'))->toThrow(InvalidArgumentException::class);
});

it('covers queue backoff and idempotency validation', function () {
    $policy = new JobPolicy();
    expect($policy->backoff(1))->toBe(10)
        ->and($policy->backoff(20))->toBe(2560)
        ->and($policy->assertIdempotencyKey(' key '))->toBe('key');
    expect(fn () => $policy->assertIdempotencyKey(' '))->toThrow(InvalidArgumentException::class);
});

it('registers permissions and enforces separation of duty', function () {
    $registry = new PermissionRegistry();
    $registry->declare('billing.invoice.read', 'billing', 'Read invoices');

    expect($registry->all())->toBe(['billing.invoice.read' => ['owner' => 'billing', 'description' => 'Read invoices']])
        ->and((new SeparationOfDuty())->permits(1, 1, false))->toBeTrue()
        ->and((new SeparationOfDuty())->permits(1, 2))->toBeTrue()
        ->and((new SeparationOfDuty())->permits(1, '1'))->toBeFalse();
    expect(fn () => $registry->declare('invalid', 'test', 'Invalid'))->toThrow(InvalidArgumentException::class);
    expect(fn () => $registry->declare('billing.invoice.read', 'billing', 'Duplicate'))->toThrow(InvalidArgumentException::class);
});

it('evaluates two-factor enforcement configuration', function () {
    $actor = Mockery::mock(Authenticatable::class);
    $policy = new TwoFactorPolicy();
    config()->set('two-factor.enforce_all', false);
    config()->set('two-factor.required_roles', ['admin']);

    expect($policy->requiredFor($actor, ['member']))->toBeFalse()
        ->and($policy->requiredFor($actor, ['admin']))->toBeTrue();
    config()->set('two-factor.enforce_all', true);
    expect($policy->requiredFor($actor))->toBeTrue();
});

it('hashes and consumes recovery codes', function () {
    $hasher = Mockery::mock(Hasher::class);
    $hasher->shouldReceive('make')->with('one')->once()->andReturn('hash-one');
    $hasher->shouldReceive('make')->with('two')->once()->andReturn('hash-two');
    $hasher->shouldReceive('check')->with('two', 'hash-one')->once()->andReturnFalse();
    $hasher->shouldReceive('check')->with('two', 'hash-two')->once()->andReturnTrue();
    $hasher->shouldReceive('check')->with('missing', 'hash-one')->once()->andReturnFalse();

    $codes = new RecoveryCodeHasher($hasher);
    $hashes = $codes->hash(['one', 'two']);
    expect($hashes)->toBe(['hash-one', 'hash-two'])
        ->and($codes->verifyAndConsume('two', $hashes))->toBeTrue()
        ->and($hashes)->toBe(['hash-one'])
        ->and($codes->verifyAndConsume('missing', $hashes))->toBeFalse();
});

it('covers webhook signing, retry and encrypted secret storage', function () {
    $signer = new WebhookSigner();
    $timestamp = time();
    $signature = $signer->sign('payload', 'secret', $timestamp);

    expect($signer->verify('payload', 'secret', $timestamp, $signature))->toBeTrue()
        ->and($signer->verify('changed', 'secret', $timestamp, $signature))->toBeFalse()
        ->and($signer->verify('payload', 'secret', $timestamp - 301, $signature))->toBeFalse()
        ->and((new RetrySchedule())->seconds(1))->toBe(30)
        ->and((new RetrySchedule())->seconds(99))->toBe(61440);

    $vault = new SigningSecretVault();
    expect($vault->open($vault->seal('secret')))->toBe('secret');
});
