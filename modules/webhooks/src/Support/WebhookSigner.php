<?php

namespace Liberu\Foundation\Webhooks\Support;

final class WebhookSigner
{
    public function sign(string $payload, string $secret, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
    }

    public function verify(string $payload, string $secret, int $timestamp, string $signature, int $tolerance = 300): bool
    {
        return abs(time() - $timestamp) <= $tolerance && hash_equals($this->sign($payload, $secret, $timestamp), $signature);
    }
}
