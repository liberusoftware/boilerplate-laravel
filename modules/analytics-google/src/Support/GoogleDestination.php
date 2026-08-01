<?php

namespace Liberu\Foundation\Analytics\Google\Support;

use Liberu\Foundation\Analytics\Contracts\AnalyticsDestination;
use Liberu\Foundation\Analytics\Data\AnalyticsEvent;
use Liberu\Foundation\Analytics\Google\Contracts\GoogleTransport;

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
