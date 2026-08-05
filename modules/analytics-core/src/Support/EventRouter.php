<?php

namespace Liberu\Analytics\Core\Support;

use Liberu\Analytics\Contracts\AnalyticsEvent;

final readonly class EventRouter
{
    public function __construct(private DestinationRegistry $destinations, private ConsentPolicy $consent) {}

    public function route(AnalyticsEvent $event, array $grants, array $allowedDestinations): array
    {
        if (! $this->consent->permits($event->consentCategory, $grants)) {
            return ['status' => 'suppressed', 'reason' => 'consent'];
        }$results = [];
        foreach ($allowedDestinations as $name) {
            $results[$name] = $this->destinations->get($name)->deliver($event);
        }

        return $results;
    }
}
