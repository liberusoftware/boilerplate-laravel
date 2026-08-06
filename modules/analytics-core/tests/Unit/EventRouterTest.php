<?php

use Liberu\Analytics\Contracts\AnalyticsDestination;
use Liberu\Analytics\Contracts\AnalyticsEvent;
use Liberu\Analytics\Core\Support\ConsentPolicy;
use Liberu\Analytics\Core\Support\DestinationRegistry;
use Liberu\Analytics\Core\Support\EventRouter;

it('routes consented events through registered destinations', function () {
    $destination = new class() implements AnalyticsDestination
    {
        public function name(): string
        {
            return 'test';
        }

        public function deliver(AnalyticsEvent $event): array
        {
            return ['accepted' => $event->id];
        }
    };
    $registry = new DestinationRegistry();
    $registry->register($destination);
    $event = new AnalyticsEvent('evt-1', 'checkout', '1', new DateTimeImmutable(), 'test', null, null, null, 'en', 'USD', 'analytics');

    expect((new EventRouter($registry, new ConsentPolicy()))->route($event, ['analytics'], ['test']))
        ->toBe(['test' => ['accepted' => 'evt-1']]);
});

it('suppresses events without required consent', function () {
    $event = new AnalyticsEvent('evt-1', 'checkout', '1', new DateTimeImmutable(), 'test', null, null, null, 'en', 'USD', 'analytics');

    expect((new EventRouter(new DestinationRegistry(), new ConsentPolicy()))->route($event, [], []))
        ->toBe(['status' => 'suppressed', 'reason' => 'consent']);
});
