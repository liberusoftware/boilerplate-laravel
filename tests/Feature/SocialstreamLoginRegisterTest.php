<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('login page renders with socialstream oauth buttons', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('oauth/');
    $response->assertSee('github');
    $response->assertSee('google');
    $response->assertSee('Or Login Via');
});

it('register page renders with socialstream oauth buttons', function () {
    $response = $this->get('/register');

    $response->assertOk();
    $response->assertSee('oauth/');
    $response->assertSee('github');
    $response->assertSee('Or Login Via');
});

it('login page contains all enabled providers', function () {
    $response = $this->get('/login');

    $response->assertOk();
    foreach (['github', 'google', 'facebook', 'gitlab', 'bitbucket', 'linkedin', 'slack', 'twitter-oauth-2'] as $provider) {
        $response->assertSee($provider);
    }
});

it('login page does not contain twitteroauth1', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertDontSee('twitter-oauth-1');
    $response->assertDontSee('twitterOAuth1');
});

it('oauth redirect route exists for github', function () {
    $response = $this->get('/oauth/github');
    // Should redirect to GitHub OAuth, not 404
    expect($response->getStatusCode())->not->toBe(404);
});
