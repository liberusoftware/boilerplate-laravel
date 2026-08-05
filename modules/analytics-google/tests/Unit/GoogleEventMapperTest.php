<?php

use Liberu\Analytics\Contracts\AnalyticsEvent;
use Liberu\Analytics\Google\Support\GoogleEventMapper;

it('maps the neutral analytics envelope to Google parameters', function () {
    $event = new AnalyticsEvent('evt-1', 'purchase', '1', new DateTimeImmutable('@1'), 'checkout', null, null, null, 'en', 'EUR', 'analytics', ['value' => 12]);

    $mapped = (new GoogleEventMapper())->map($event);

    expect($mapped['name'])->toBe('purchase')
        ->and($mapped['params']['value'])->toBe(12)
        ->and($mapped['params']['currency'])->toBe('EUR');
});

it('rejects provider-invalid event names', function () {
    $event = new AnalyticsEvent('evt-1', str_repeat('x', 41), '1', new DateTimeImmutable(), 'test', null, null, null, null, null, 'analytics');

    (new GoogleEventMapper())->map($event);
})->throws(InvalidArgumentException::class);
