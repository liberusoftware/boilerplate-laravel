<?php

namespace Liberu\Analytics\Google\Support;

use InvalidArgumentException;
use Liberu\Analytics\Contracts\AnalyticsEvent;

final class GoogleEventMapper
{
    public function map(AnalyticsEvent $event): array
    {
        if (strlen($event->name) > 40) {
            throw new InvalidArgumentException('Google event name exceeds 40 characters.');
        }

        return ['name' => $event->name, 'params' => $event->properties + ['currency' => $event->currency, 'engagement_time_msec' => 1], 'timestamp_micros' => $event->occurredAt->format('Uu')];
    }
}
