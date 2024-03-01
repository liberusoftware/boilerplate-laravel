<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;

class HavingTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function testSimple()
    {
        // One column
        $this->compare(User::groupBy(['id'])->having('id', '>', 1));

        // Two columns
        $this->compare(User::groupBy(['id', 'title'])->having('id', '>', 1)->orHaving('title', '=', 'abc'));

        // Raw
        $this->compare(User::groupBy('id')->havingRaw('COUNT(id) > ?', [1]));
    }
}
