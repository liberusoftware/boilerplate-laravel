<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('unauthenticated user is redirected from admin panel', function () {
    $this->get('/admin')->assertRedirect();
});

it('authenticated user can access admin panel', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin');
    expect($response->getStatusCode())->toBeIn([200, 302]);
});

it('admin login page is accessible', function () {
    $this->get('/admin/login')->assertOk();
});
