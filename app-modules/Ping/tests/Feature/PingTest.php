<?php

namespace Modules\Ping\Tests\Feature;

use Tests\TestCase;

class PingTest extends TestCase
{
    public function test_module_index(): void
    {
        $response = $this->get(route('ping.index'));
        $response->assertStatus(200);
    }
}
