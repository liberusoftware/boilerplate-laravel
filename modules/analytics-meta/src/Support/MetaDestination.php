<?php

namespace Liberu\Analytics\Meta\Support;

use Liberu\Analytics\Contracts\AnalyticsDestination;
use Liberu\Analytics\Contracts\AnalyticsEvent;
use Liberu\Analytics\Meta\Contracts\MetaTransport;

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
