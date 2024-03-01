<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;

class UnionTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function testSimple()
    {
        $this->compare(
            User::whereIn('title', ['a', 'b'])->union(User::whereIn('id', ['1', '2']))
        );
    }

    /**
     * @return void
     */
    public function testNested()
    {
        // 2 levels
        $union = User::where('id', '=', '1');
        $union = User::where('id', '=', '2')->union($union);

        $this->compare(
            User::whereIn('title', ['a', 'b'])->union($union)
        );

        // 3 levels
        $union = User::where('id', '=', '1');
        $union = User::where('id', '=', '2')->union($union);
        $union = User::where('id', '=', '3')->union($union);

        $this->compare(
            User::whereIn('title', ['a', 'b'])->union($union)
        );
    }

    /**
     * @return void
     */
    public function testExpression()
    {
        $union1 = User::where(function ($query) {
            $query->whereDoesnthave('userPhones', function ($query) {
                $query->where(function ($query) {
                    $query
                        ->where('created_at', '>=', '2010-01-01')
                        ->orWhere('id', '=', '1');
                });
            });
        });

        $union2 = User::whereHas('userPhones', function ($query) {
            $query->where('created_at', '>=', '2010-01-01');
        });

        $union2 = User::union($union2)->where(function ($query) {
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
        });

        $this->compare(
            User::whereNotIn('title', ['a', 'b'])->union($union1)->union($union2, true)
        );
    }
}
