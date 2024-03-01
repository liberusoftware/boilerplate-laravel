<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;

class LimitTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function testSimple()
    {
        // Limit
        $this->compare(User::limit(10));

        // Limit with offset
        $this->compare(User::offset(20)->limit(10));
    }
}
