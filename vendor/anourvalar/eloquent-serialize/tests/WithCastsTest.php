<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;
use AnourValar\EloquentSerialize\Tests\Models\UserPhone;

class WithCastsTest extends AbstractSuite
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentSerialize\Tests\AbstractTest::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (! method_exists(User::class, 'mergeCasts')) {
            $this->markTestSkipped('Old version.');
        }
    }

    /**
     * @return void
     */
    public function testSimple()
    {
        // Common
        $this->compare(User::withCasts(['user_id' => 'integer']));

        // Custom
        $this->compare(User::withCasts(['user_id' => \AnourValar\EloquentSerialize\Tests\Casts\TestCast::class]));
    }

    /**
     * @return void
     */
    public function testSelectRaw()
    {
        $this->compare(
            User::select([
                'users.*',
                'last_phone_created_at' => UserPhone::selectRaw('MAX(created_at)')->whereColumn('user_id', 'users.id'),
            ])->withCasts([
                'last_phone_created_at' => 'datetime',
            ])
        );
    }
}
