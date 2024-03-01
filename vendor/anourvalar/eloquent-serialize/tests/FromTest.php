<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;

class FromTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function testSimple()
    {
        $this->compare(
            User::whereExists(function ($query) {
                $query
                    ->from('user_phones')
                    ->whereRaw('user_phones.user_id = users.id');
            })
        );
    }
}
