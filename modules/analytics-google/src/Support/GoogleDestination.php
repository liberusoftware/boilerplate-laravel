<?php

namespace Liberu\Analytics\Google\Support;

use Liberu\Analytics\Contracts\AnalyticsDestination;
use Liberu\Analytics\Contracts\AnalyticsEvent;
use Liberu\Analytics\Google\Contracts\GoogleTransport;

final readonly class GoogleDestination implements AnalyticsDestination
{
    public function __construct(private GoogleEventMapper $mapper, private GoogleTransport $transport) {}

    public function name(): string
    {
        return 'google';
    }

    public function deliver(AnalyticsEvent $event): array
    {
        return $this->transport->send($this->mapper->map($event));
    }
}
