<?php

namespace Liberu\Foundation\DeveloperExperience\Support;

final class EnvironmentDoctor
{
    /** @return list<string> */
    public function inspect(array $extensions, array $writablePaths): array
    {
        $errors = [];
        foreach ($extensions as $extension) {
            if (! extension_loaded($extension)) {
                $errors[] = "Missing PHP extension: {$extension}";
            }
        }
        foreach ($writablePaths as $path) {
            if (! is_writable($path)) {
                $errors[] = "Not writable: {$path}";
            }
        }

        return $errors;
    }
}
