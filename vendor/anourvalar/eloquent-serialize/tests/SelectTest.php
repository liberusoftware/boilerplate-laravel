<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;
use AnourValar\EloquentSerialize\Tests\Models\UserPhone;

class SelectTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function testSimple()
    {
        // List of columns
        $this->compare(User::select(['id', 'title']));

        // Raw
        $this->compare(User::selectRaw('id, (SELECT COUNT(*) FROM user_phones WHERE user_id = users.id)'));
    }

    /**
     * @return void
     */
    public function testExpression()
    {
        $this->compare(
            User::select(['users.*', 'test' => UserPhone::selectRaw('MAX(created_at)')->whereColumn('user_id', 'users.id')])
        );
    }
}
