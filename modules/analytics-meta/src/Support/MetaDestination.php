<?php

namespace Liberu\Foundation\Analytics\Meta\Support;

use Liberu\Foundation\Analytics\Contracts\AnalyticsDestination;
use Liberu\Foundation\Analytics\Data\AnalyticsEvent;
use Liberu\Foundation\Analytics\Meta\Contracts\MetaTransport;

final readonly class MetaDestination implements AnalyticsDestination
{
    public function __construct(private MetaTransport $transport) {}

    public function name(): string
    {
        return 'meta';
    }

    public function deliver(AnalyticsEvent $event): array
    {
        return $this->transport->send(['event_name' => $event->name, 'event_time' => $event->occurredAt->getTimestamp(), 'event_id' => $event->id, 'custom_data' => $event->properties]);
    }
}
