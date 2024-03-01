<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Http;

use Generator;
use Spiral\RoadRunner\WorkerAwareInterface;
use Stringable;

/**
 * @psalm-import-type HeadersList from Request
 */
interface HttpWorkerInterface extends WorkerAwareInterface
{
    /**
     * Wait for incoming http request.
     */
    public function waitRequest(): ?Request;

    /**
     * Send response to the application server.
     *
     * @param int $status Http status code
     * @param Generator<mixed, scalar|Stringable, mixed, Stringable|scalar|null>|string $body Body of response.
     *        If the body is a generator, then each yielded value will be sent as a separated stream chunk.
     *        Returned value will be sent as a last stream package.
     *        Note: Stream response is supported by RoadRunner since version 2023.3
     * @param HeadersList|array $headers An associative array of the message's headers. Each key MUST be a header name,
     *                                   and each value MUST be an array of strings for that header.
     */
    public function respond(int $status, string|Generator $body, array $headers = []): void;
}
