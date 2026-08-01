<?php

namespace Liberu\Foundation\Analytics\Support;

final class ConsentPolicy
{
    public function permits(string $category, array $grants): bool
    {
        return $category === 'strictly-necessary' || in_array($category, $grants, true);
    }
}
