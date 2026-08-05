<?php

namespace Liberu\Analytics\Meta\Support;

final class MetaCustomerNormalizer
{
    public function email(string $value): string
    {
        return hash('sha256', mb_strtolower(trim($value)));
    }

    public function phone(string $value): string
    {
        return hash('sha256', preg_replace('/\D+/', '', $value));
    }

    public function externalId(string $value, string $salt): string
    {
        return hash_hmac('sha256', trim($value), $salt);
    }
}
