<?php

use Liberu\Analytics\Contracts\AnalyticsDestination;
use Liberu\Analytics\Contracts\AnalyticsEvent;
use Liberu\Foundation\Analytics\Google\Contracts\GoogleTransport;
use Liberu\Foundation\Analytics\Google\Support\GoogleDestination;
use Liberu\Foundation\Analytics\Google\Support\GoogleEventMapper;
use Liberu\Foundation\Analytics\Meta\Contracts\MetaTransport;
use Liberu\Foundation\Analytics\Meta\Support\MetaDestination;
use Liberu\Foundation\Analytics\Support\DestinationRegistry;

function coverageAnalyticsEvent(): AnalyticsEvent
{
    return new AnalyticsEvent('event-id', 'purchase', '1', new DateTimeImmutable('@100'), 'test', null, null, null, 'en', 'USD', 'analytics', ['value' => 10]);
}

it('delivers mapped events to Google and Meta transports', function () {
    $google = Mockery::mock(GoogleTransport::class);
    $google->shouldReceive('send')->once()->with(Mockery::on(fn (array $payload) => $payload['name'] === 'purchase'))->andReturn(['ok' => true]);
    $meta = Mockery::mock(MetaTransport::class);
    $meta->shouldReceive('send')->once()->with(Mockery::on(fn (array $payload) => $payload['event_name'] === 'purchase'))->andReturn(['ok' => true]);

    expect((new GoogleDestination(new GoogleEventMapper(), $google))->name())->toBe('google')
        ->and((new GoogleDestination(new GoogleEventMapper(), $google))->deliver(coverageAnalyticsEvent()))->toBe(['ok' => true])
        ->and((new MetaDestination($meta))->name())->toBe('meta')
        ->and((new MetaDestination($meta))->deliver(coverageAnalyticsEvent()))->toBe(['ok' => true]);
});

it('rejects duplicate and unknown analytics destinations', function () {
    $destination = Mockery::mock(AnalyticsDestination::class);
    $destination->shouldReceive('name')->andReturn('test');
    $registry = new DestinationRegistry();
    $registry->register($destination);

    expect($registry->get('test'))->toBe($destination)
        ->and($registry->all())->toBe(['test' => $destination])
        ->and(fn () => $registry->register($destination))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $registry->get('missing'))->toThrow(InvalidArgumentException::class);
});
