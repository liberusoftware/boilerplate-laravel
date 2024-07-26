<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test the root route ("/") returns a successful response.
     */
    public function test_the_root_route_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /**
     * Test the "/app" route returns a successful response.
     */
    public function test_the_app_route_returns_a_successful_response(): void
    {
        $response = $this->get('/app');
        $response->assertStatus(200);
    }

    /**
     * Test the "/admin" route returns a successful response.
     */
    public function test_the_admin_route_returns_a_successful_response(): void
    {
        $response = $this->get('/admin');
        $response->assertStatus(200);
    }
}
