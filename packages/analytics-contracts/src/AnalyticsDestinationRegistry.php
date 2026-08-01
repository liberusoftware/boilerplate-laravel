<?php

namespace Liberu\Analytics\Contracts;

interface AnalyticsDestinationRegistry
{
    public function register(AnalyticsDestination $destination): void;

    public function get(string $name): AnalyticsDestination;

    /** @return array<string, AnalyticsDestination> */
    public function all(): array;
}
