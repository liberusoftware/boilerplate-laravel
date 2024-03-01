<?php

namespace AnourValar\EloquentSerialize\Tests;

use AnourValar\EloquentSerialize\Tests\Models\User;
use AnourValar\EloquentSerialize\Tests\Models\UserPhone;

class EagerTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function testSimple()
    {
        // ...
        $this->compare(User::query());

        // with
        $this->compare(User::with('userPhones'));

        // with count
        $this->compare(User::withCount('userPhones'));
    }

    /**
     * @return void
     */
    public function testComplex()
    {
        // with
        $this->compare(User::with('userPhonesSorted'));
        $this->compare(User::with('userPhonesPrimary'));
        $this->compare(User::with(['userPhonesSorted', 'userPhonesPrimary']));
        $this->compare(User::query()->with('filesAB', 'filesC', 'filesDE'));

        // with count
        $this->compare(User::withCount('userPhonesSorted'));
        $this->compare(User::withCount('userPhonesPrimary'));
        $this->compare(User::withCount(['userPhonesSorted', 'userPhonesPrimary']));
        $this->compare(User::query()->withCount('filesAB', 'filesC', 'filesDE'));
    }

    /**
     * @return void
     */
    public function testNested()
    {
        // with
        $this->compare(User::with('userPhones.userPhoneNote'));

        // with (reverse)
        $this->compare(UserPhone::with('user.userPhones'));
    }

    /**
     * @return void
     */
    public function testNestedComplex()
    {
        // with
        $this->compare(User::with('userPhonesSorted.userPhoneNote'));
        $this->compare(User::with('userPhonesPrimary.userPhoneNote'));

        // with (reverse)
        $this->compare(UserPhone::with('user.userPhonesSorted'));
        $this->compare(UserPhone::with('user.userPhonesPrimary'));
    }

    /**
     * @return void
     */
    public function testWithBuilder()
    {
        // 1 level
        $this->compare(
            User::with(['userPhones' => function ($query) {
                $query->orderBy('id', 'ASC')->limit(1)->select(['id', 'phone']);
            }])
        );

        // 2 levels
        $this->compare(
            User::with(['userPhones' => function ($query) {
                $query->where(function ($query) {
                    $query->where('phone', '=', '111')->orWhere('phone', '!=', '222')->limit(5);
                });
            }])
        );

        // 3 levels
        $this->compare(
            User::with(['userPhones' => function ($query) {
                $query->where(function ($query) {
                    $query
                        ->where('phone', '=', '111')
                        ->orWhere(function ($query) {
                            $query->where('phone', '=', '222')->orWhere('created_at', '>', '2010-01-01');
                        });
                });
            }])
        );
    }

    /**
     * @return void
     */
    public function testWithComplexBuilder()
    {
        // 1 level
        $this->compare(
            User::with(['userPhonesPrimary' => function ($query) {
                $query->orderBy('id', 'ASC')->limit(1)->select(['id', 'phone']);
            }])
        );

        // 2 levels
        $this->compare(
            User::with(['userPhonesPrimary' => function ($query) {
                $query->where(function ($query) {
                    $query->where('phone', '=', '111')->orWhere('phone', '!=', '222');
                });
            }])
        );

        // 3 levels
        $this->compare(
            User::with(['userPhonesPrimary' => function ($query) {
                $query->where(function ($query) {
                    $query
                        ->where('phone', '=', '111')
                        ->orWhere(function ($query) {
                            $query->where('phone', '=', '222')->orWhere('created_at', '>', '2010-01-01');
                        });
                });
            }])
        );
    }

    /**
     * @return void
     */
    public function testWithCountBuilder()
    {
        // 1 level
        $this->compare(
            User::withCount(['userPhones' => function ($query) {
                $query->limit(2);
            }])
        );

        // 2 levels
        $this->compare(
            User::withCount(['userPhones' => function ($query) {
                $query->where(function ($query) {
                    $query->where('phone', '=', '111')->orWhere('phone', '!=', '222');
                });
            }])
        );
    }

    /**
     * @return void
     */
    public function testWithComplexCountBuilder()
    {
        // 1 level
        $this->compare(
            User::withCount(['userPhonesSorted' => function ($query) {
                $query->limit(2);
            }])
        );

        // 2 levels
        $this->compare(
            User::withCount(['userPhonesSorted' => function ($query) {
                $query->where(function ($query) {
                    $query->where('phone', '=', '111')->orWhere('phone', '!=', '222');
                });
            }])
        );
    }

    /**
     * @return void
     */
    public function testWithCountAlias()
    {
        // simple
        $this->compare(
            User::withCount('userPhones as test')
        );

        // builder
        $this->compare(
            User::withCount([
                'userPhones as primary' => function ($query) {
                    $query->where(function ($query) {
                        $query->where('is_primary', true);
                    });
                },
                'userPhones as not_primary' => function ($query) {
                    $query->where('is_primary', false);
                },
            ])
        );
    }

    /**
     * @return void
     */
    public function testBelongs()
    {
        // simple
        $this->compare(
            UserPhone::with('user')
        );

        // simple count
        $this->compare(
            UserPhone::withCount('user')
        );

        // builder
        $this->compare(
            UserPhone::with([
                'user' => function ($query) {
                    $query->where('title', '=', 'admin')->limit(1);
                },
            ])
        );
    }

    /**
     * @return void
     */
    public function testHasManyThrough()
    {
        // simple
        $this->compare(User::with('userPhoneNote'));
        $this->compare(User::with('userPhonesSorted'));
        $this->compare(User::with('userPhonesPrimary'));

        // simple count
        $this->compare(User::withCount('userPhoneNote'));
        $this->compare(User::withCount('userPhonesSorted'));
        $this->compare(User::withCount('userPhonesPrimary'));

        // builder
        $this->compare(
            User::with([
                'userPhoneNote' => function ($query) {
                    $query->limit(1);
                },
            ])
        );
        $this->compare(
            User::with([
                'userPhonesSorted' => function ($query) {
                    $query->limit(1);
                },
            ])
        );
        $this->compare(
            User::with([
                'userPhonesPrimary' => function ($query) {
                    $query->limit(1);
                },
            ])
        );
    }

    /**
     * @return void
     */
    public function testThroughBuilder()
    {
        // simple
        $this->compare(User::with('userPhoneNoteAlt'));

        // simple count
        $this->compare(User::withCount('userPhoneNoteAlt'));

        // builder
        $this->compare(
            User::with([
                'userPhoneNoteAlt' => function ($query) {
                    $query->limit(1);
                },
            ])
        );
    }

    /**
     * @return void
     */
    public function testHasOne()
    {
        // simple
        $this->compare(
            UserPhone::with('userPhoneNote')
        );

        // simple count
        $this->compare(
            UserPhone::withCount('userPhoneNote')
        );

        // builder
        $this->compare(
            UserPhone::with([
                'userPhoneNote' => function ($query) {
                    $query->whereNotNull('note');
                },
            ])
        );
    }
}
