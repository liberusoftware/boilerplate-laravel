<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;
use AnourValar\EloquentSerialize\Tests\Models\UserPhone;

class OrderByTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function testSimple()
    {
        // One column
        $this->compare(User::orderBy('id', 'ASC'));

        // Two columns
        $this->compare(User::orderBy('id', 'ASC')->orderBy('sort', 'DESC'));
    }

    /**
     * @return void
     */
    public function testExpression()
    {
        // ASC
        $this->compare(
            User::orderBy(UserPhone::select('created_at')->whereColumn('user_id', 'users.id')->limit(1)->orderBy('created_at', 'ASC'))
        );

        // DESC
        $this->compare(
            User::orderByDesc(UserPhone::select('phone')->whereColumn('user_id', 'users.id')->limit(1)->orderBy('phone', 'DESC'))
        );
    }
}
