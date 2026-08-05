<?php

use Liberu\Analytics\Contracts\AnalyticsEvent;
use Liberu\Analytics\Google\Contracts\GoogleTransport;
use Liberu\Analytics\Google\Support\GoogleDestination;
use Liberu\Analytics\Google\Support\GoogleEventMapper;

it('delivers mapped events to the Google transport under its registered name', function () {
    $event = new AnalyticsEvent('event-id', 'purchase', '1', new DateTimeImmutable('@100'), 'test', null, null, null, 'en', 'USD', 'analytics', ['value' => 10]);

    $transport = Mockery::mock(GoogleTransport::class);
    $transport->shouldReceive('send')->once()->with(Mockery::on(fn (array $payload) => $payload['name'] === 'purchase'))->andReturn(['ok' => true]);

    $destination = new GoogleDestination(new GoogleEventMapper(), $transport);

    expect($destination->name())->toBe('google')
        ->and($destination->deliver($event))->toBe(['ok' => true]);
});
