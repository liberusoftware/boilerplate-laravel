<?php

namespace AnourValar\EloquentSerialize\Tests\Models;

class User extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userPhones()
    {
        return $this->hasMany(UserPhone::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userPhonesSorted()
    {
        return $this->hasMany(UserPhone::class)->orderBy('phone', 'ASC');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userPhonesPrimary()
    {
        return $this->hasMany(UserPhone::class)->where('is_primary', '=', true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function userPhoneNote()
    {
        return $this->hasManyThrough(UserPhoneNote::class, UserPhone::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function userPhoneNoteAlt()
    {
        return $this->through($this->userPhones())
            ->has(fn (UserPhone $userPhone) => $userPhone->userPhoneNote());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function filesAB()
    {
        return $this->hasMany(File::class, 'user_id')->whereIn('type', ['a', 'b']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function filesC()
    {
        return $this->hasMany(File::class, 'user_id', 'id')->where('type', 'c');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function filesDE()
    {
        return $this->hasOne(File::class)
            ->whereNotIn('type', ['f', 'g'])
            ->whereIn('type', ['d', 'e'])
            ->whereNotIn('type', ['a', 'b', 'c']);
    }
}
