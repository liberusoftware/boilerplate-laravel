<?php

namespace Liberu\Foundation\ImportExport\Data;

use InvalidArgumentException;

final readonly class TransferSchema
{
    /** @param array<string,array{required?:bool,type:string}> $fields */
    public function __construct(public string $name, public string $version, public array $fields)
    {
        if ($fields === []) {
            throw new InvalidArgumentException('Transfer schema requires fields.');
        }
    }
}
