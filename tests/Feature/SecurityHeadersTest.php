<?php

it('does not expose PHP version header', function () {
    $response = $this->get('/');
    expect($response->headers->has('X-Powered-By'))->toBeFalse();
});

it('response contains correct content type for json api', function () {
    $response = $this->getJson('/api/search/users');
    expect($response->headers->get('Content-Type'))->toContain('application/json');
});

it('csrf protection is active on post routes', function () {
    $response = $this->post('/login', []);
    expect($response->getStatusCode())->not->toBe(200);
});
