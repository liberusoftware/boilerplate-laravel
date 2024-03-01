<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;

class DistinctTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function testSimple()
    {
        $this->compare(User::distinct('title'));
    }
}
