<?php

it('renders the Clear Signal landing at /', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('A foundation you', false); // hero headline
    $response->assertSee('Get started free', false);       // primary CTA
    $response->assertSee('Clear Signal', false);            // brand system
    $response->assertSee('https://github.com/liberusoftware/boilerplate-laravel', false);
});

it('links the landing CTAs to the real auth routes', function () {
    $this->get('/')
        ->assertSee(route('register'), false)
        ->assertSee(route('login'), false);
});

it('no longer ships the stock Laravel welcome scaffold', function () {
    $this->get('/')->assertDontSee("Let's get started", false);
});
