<?php

namespace Liberu\Foundation\ImportExport\Contracts;

interface TransferAuthorizer
{
    public function allowed(string|int $actorId, string $schema, string $direction): bool;
}
