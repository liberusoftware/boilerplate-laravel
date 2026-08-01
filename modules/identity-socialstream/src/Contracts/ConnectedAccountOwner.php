<?php

namespace Liberu\Foundation\Identity\Socialstream\Contracts;

interface ConnectedAccountOwner
{
    /** @param mixed $account */
    public function ownsConnectedAccount($account);
}
