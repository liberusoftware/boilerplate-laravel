<?php

use Liberu\Analytics\Contracts\AnalyticsEvent;
use Liberu\Analytics\Meta\Contracts\MetaTransport;
use Liberu\Analytics\Meta\Support\MetaDestination;

it('delivers events to the Meta transport under its registered name', function () {
    $event = new AnalyticsEvent('event-id', 'purchase', '1', new DateTimeImmutable('@100'), 'test', null, null, null, 'en', 'USD', 'analytics', ['value' => 10]);

    $transport = Mockery::mock(MetaTransport::class);
    $transport->shouldReceive('send')->once()->with(Mockery::on(fn (array $payload) => $payload['event_name'] === 'purchase'))->andReturn(['ok' => true]);

    $destination = new MetaDestination($transport);

    expect($destination->name())->toBe('meta')
        ->and($destination->deliver($event))->toBe(['ok' => true]);
});
