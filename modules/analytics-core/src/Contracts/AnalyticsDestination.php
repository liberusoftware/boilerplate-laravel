<?php

namespace Liberu\Foundation\Analytics\Contracts;

use Liberu\Foundation\Analytics\Data\AnalyticsEvent;

interface AnalyticsDestination
{
    public function name(): string;

    public function deliver(AnalyticsEvent $event): array;
}
