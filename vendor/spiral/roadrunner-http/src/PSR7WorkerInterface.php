<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\RoadRunner\WorkerAwareInterface;

interface PSR7WorkerInterface extends WorkerAwareInterface
{
    public function waitRequest(): ?ServerRequestInterface;

    /**
     * Send response to the application server.
     */
    public function respond(ResponseInterface $response): void;
}
