<?php

use Illuminate\Support\Str;

// These pages 500'd before the Clear Signal auth components were published
// (the Jetstream auth components were never installed). Each must now render.

it('renders the login page in the Clear Signal style', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('cs-card', false)          // shared Clear Signal card
        ->assertSee('Back to home', false);    // shared authentication-card chrome
});

it('renders the registration page in the Clear Signal style', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('cs-card', false)
        ->assertSee('Back to home', false);
});

it('renders the forgot-password page', function () {
    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('cs-card', false);
});

it('renders the reset-password page', function () {
    $this->get('/reset-password/'.Str::random(40))
        ->assertOk()
        ->assertSee('cs-card', false);
});
