<?php

namespace Liberu\Foundation\Files\Support;

use InvalidArgumentException;

final class UploadPolicy
{
    /** @param list<string> $allowedMimeTypes */
    public function assert(string $mimeType, int $bytes, array $allowedMimeTypes, int $maxBytes): void
    {
        if ($bytes < 1 || $bytes > $maxBytes) {
            throw new InvalidArgumentException('File size is not permitted.');
        }
        if (! in_array($mimeType, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException('File type is not permitted.');
        }
    }
}
