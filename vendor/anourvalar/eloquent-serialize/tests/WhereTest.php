<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\Post;
use AnourValar\EloquentSerialize\Tests\Models\User;
use AnourValar\EloquentSerialize\Tests\Models\UserPhone;

class WhereTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function testSimple()
    {
        // One column
        $this->compare(User::where('id', '=', '1'));

        // Two columns
        $this->compare(User::where('id', '=', '1')->orWhere('id', '=', '2'));
    }

    /**
     * @return void
     */
    public function testExpression()
    {
        // Raw
        $this->compare(
            User::whereRaw('(id = ? or (SELECT COUNT(*) FROM user_phones WHERE user_id = users.id) > ?)', [5, 1])
        );

        // DB Raw
        $this->compare(
            User::where(\DB::raw('(id = ? or (SELECT COUNT(*) FROM user_phones WHERE user_id = users.id) > ?)', [5, 1]))
        );

        // 1 level
        $this->compare(
            User::where(function ($query) {
                $query->where('id', '=', '1')->orWhere('id', '=', 2);
            })
        );

        // 2 levels
        $this->compare(
            User::where(function ($query) {
                $query
                    ->where('id', '=', '1')
                    ->orWhere(function ($query) {
                        $query->where('id', '=', '2')->where('title', '!=', 'admin');
                    });
            })
        );

        // 3 levels
        $this->compare(
            User::where(function ($query) {
                $query
                    ->where('id', '=', '1')
                    ->orWhere(function ($query) {
                        $query
                            ->where('id', '=', '2')
                            ->orWhere(function ($query) {
                                $query
                                    ->where('title', '!=', 'admin')
                                    ->orWhere('id', '=', '3');
                            });
                    });
            })
        );
    }

    /**
     * @return void
     */
    public function testHas()
    {
        // has
        $this->compare(User::has('userPhones'));
        $this->compare(User::has('filesAB')->has('filesC'));

        // whereHas, 1 level
        $this->compare(
            User::whereHas('userPhones', function ($query) {
                $query->where('created_at', '>=', '2010-01-01');
            })
        );

        $this->compare(
            User::whereHas('filesAB', function ($query) {
                $query->whereIn('type', ['f', 'g']);
            })
        );

        // whereHas, X levels
        $this->compare(
            User::where(function ($query) {
                $query->whereHas('userPhones', function ($query) {
                    $query->where(function ($query) {
                        $query
                            ->where('created_at', '>=', '2010-01-01')
                            ->orWhere('id', '=', '1');
                    });
                });
            })
        );
    }

    /**
     * @return void
     */
    public function testWithHas()
    {
        // withWhereHas, 1 level
        $this->compare(
            User::withWhereHas('userPhones', function ($query) {
                $query->where('created_at', '>=', '2010-01-01');
            })
        );

        $this->compare(
            User::withWhereHas('userPhones:id,is_primary')
        );

        $this->compare(
            User::withWhereHas('filesAB', function ($query) {
                $query->whereIn('type', ['f', 'g']);
            })
        );

        // withWhereHas, X levels
        $this->compare(
            User::where(function ($query) {
                $query->withWhereHas('userPhones', function ($query) {
                    $query->where(function ($query) {
                        $query
                            ->where('created_at', '>=', '2010-01-01')
                            ->orWhere('id', '=', '1');
                    });
                });
            })
        );
    }

    /**
     * @return void
     */
    public function testNestedHas()
    {
        // has
        $this->compare(User::has('userPhones.userPhoneNote'));

        // whereHas, 1 level
        $this->compare(
            User::whereHas('userPhones.userPhoneNote', function ($query) {
                $query->where('created_at', '>=', '2010-01-01');
            })
        );

        // whereHas, X levels
        $this->compare(
            User::where(function ($query) {
                $query->whereHas('userPhones.userPhoneNote', function ($query) {
                    $query->where(function ($query) {
                        $query
                            ->where('created_at', '>=', '2010-01-01')
                            ->orWhere('id', '=', '1');
                    });
                });
            })
        );
    }

    /**
     * @return void
     */
    public function testNestedWithHas()
    {
        // withWhereHas, 1 level
        $this->compare(
            User::withWhereHas('userPhones.userPhoneNote', function ($query) {
                $query->where('created_at', '>=', '2010-01-01');
            })
        );

        // withWhereHas, X levels
        $this->compare(
            User::where(function ($query) {
                $query->withWhereHas('userPhones.userPhoneNote', function ($query) {
                    $query->where(function ($query) {
                        $query
                            ->where('created_at', '>=', '2010-01-01')
                            ->orWhere('id', '=', '1');
                    });
                });
            })
        );
    }

    /**
     * @return void
     */
    public function testDoesnthave()
    {
        // doesnthave
        $this->compare(User::doesnthave('userPhones'));
        $this->compare(User::doesnthave('filesAB')->doesnthave('filesC'));

        // whereDoesnthave
        $this->compare(
            User::whereDoesnthave('userPhones', function ($query) {
                $query->where('created_at', '>=', '2010-01-01');
            })
        );

        // whereDoesnthave, X levels
        $this->compare(
            User::where(function ($query) {
                $query->whereDoesnthave('userPhones', function ($query) {
                    $query->where(function ($query) {
                        $query
                            ->where('created_at', '>=', '2010-01-01')
                            ->orWhere('id', '=', '1');
                    });
                });
            })
        );
    }

    /**
     * @return void
     */
    public function testJson()
    {
        $this->compare(User::where('meta->foo', 'a'));

        $this->compare(User::whereJsonContains('meta->foo', ['a']), false);

        $this->compare(User::whereJsonDoesntContain('meta->foo', ['a']), false);

        $this->compare(User::whereJsonLength('meta->foo', 0));
        $this->compare(User::whereJsonLength('meta->foo', '>', 1));

        $this->compare(User::whereJsonContainsKey('meta->foo'));
        $this->compare(User::whereJsonContainsKey('meta->foo[0]'));
        $this->compare(User::whereJsonContainsKey('meta->foo->bar'));
        $this->compare(User::whereJsonContainsKey('meta->foo->bar[0]'));

        $this->compare(User::whereJsonDoesntContainKey('meta->foo'));
        $this->compare(User::whereJsonDoesntContainKey('meta->foo[0]'));
        $this->compare(User::whereJsonDoesntContainKey('meta->foo->bar'));
        $this->compare(User::whereJsonDoesntContainKey('meta->foo->bar[0]'));
    }

    /**
     * @return void
     */
    public function testFullText()
    {
        // Simple
        $this->compare(Post::whereFullText('body', 'said'), false);

        // With options
        $this->compare(Post::whereFullText('body', 'said', ['language' => 'russian']), false);

        // Inside closure
        $this->compare(
            User::whereHas('userPhoneNote', function ($query) {
                $query->whereFullText('note', 'another', ['language' => 'russian']);
            }),
            false
        );
    }

    /**
     * @return void
     */
    public function testBelongsTo()
    {
        $this->compare(
            UserPhone::whereBelongsTo(UserPhone::has('user')->first()->user)
        );
    }
}
