<?php

use Liberu\Analytics\Meta\Support\MetaCustomerNormalizer;

it('normalizes customer identifiers before one-way hashing', function () {
    $normalizer = new MetaCustomerNormalizer();

    expect($normalizer->email(' Test@Example.COM '))->toBe(hash('sha256', 'test@example.com'))
        ->and($normalizer->phone('+1 (202) 555-0100'))->toBe(hash('sha256', '12025550100'))
        ->and($normalizer->externalId(' actor-1 ', 'salt'))->toBe(hash_hmac('sha256', 'actor-1', 'salt'));
});
