<?php

it('adds baseline security headers to web responses', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy');
});

it('does not send HSTS over plain HTTP', function () {
    // Local/dev http requests must not be pinned to HTTPS.
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
});
