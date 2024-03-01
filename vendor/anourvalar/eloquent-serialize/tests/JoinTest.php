<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\Post;
use AnourValar\EloquentSerialize\Tests\Models\User;
use AnourValar\EloquentSerialize\Tests\Models\UserPhone;

class JoinTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function testLeft()
    {
        $this->compare(
            User::leftJoin('user_phones', 'users.id', '=', 'user_phones.user_id')->selectRaw('users.*, user_phones.phone')
        );

        $this->compare(
            UserPhone::leftJoin('users', 'users.id', '=', 'user_phones.user_id')->groupBy('users.id')
        );
    }

    /**
     * @return void
     */
    public function testInner()
    {
        $this->compare(
            UserPhone::join('users', 'users.id', '=', 'user_phones.user_id')
        );
    }

    /**
     * @return void
     */
    public function testCross()
    {
        $this->compare(UserPhone::crossJoin('users'));
    }

    /**
     * @return void
     */
    public function testMultiple()
    {
        $this->compare(
            UserPhone::join('users', 'users.id', '=', 'user_phones.user_id')
                ->join('posts', 'users.id', '=', 'posts.user_id')
        );
    }

    /**
     * @return void
     */
    public function testExpression()
    {
        $this->compare(
            User::join('posts', function ($join) {
                $join->on('users.id', '=', 'posts.user_id')->orOn('users.id', '=', 'posts.user_id');
            })
        );

        $this->compare(
            User::join('posts', function ($join) {
                $join->on('users.id', '=', 'posts.user_id')->where('posts.title', '=', 'abc');
            })
        );
    }

    /**
     * @return void
     */
    public function testSubQuery()
    {
        $latestPosts = Post::select('user_id', \DB::raw('MAX(created_at) as last_post_created_at'))
           ->groupBy('user_id');

        $this->compare($latestPosts);

        $this->compare(
            User::joinSub($latestPosts, 'latest_posts', function ($join) {
                $join->on('users.id', '=', 'latest_posts.user_id');
            })
        );
    }
}
