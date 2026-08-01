<?php

namespace Liberu\Foundation\Identity\Socialstream\Actions;

use Illuminate\Http\Response;
use JoelButcher\Socialstream\Contracts\HandlesInvalidState;
use Laravel\Socialite\Two\InvalidStateException;

class HandleInvalidState implements HandlesInvalidState
{
    /**
     * Handle an invalid state exception from a Socialite provider.
     */
    public function handle(InvalidStateException $exception): Response
    {
        throw $exception;
    }
}
