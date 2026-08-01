<?php

namespace Liberu\Foundation\Files\Contracts;

interface MediaTransformer
{
    public function transform(string $source, string $operation, array $parameters = []): string;
}
