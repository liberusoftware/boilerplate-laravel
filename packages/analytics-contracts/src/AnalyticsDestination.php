<?php

namespace Liberu\Analytics\Contracts;

interface AnalyticsDestination
{
    public function name(): string;

    /** @return array<string, mixed> */
    public function deliver(AnalyticsEvent $event): array;
}
