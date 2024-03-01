<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Http\Exception;

final class StreamStoppedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Stream has been stopped by the client.');
    }
}
