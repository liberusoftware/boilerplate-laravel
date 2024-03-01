<?php

namespace AnourValar\EloquentSerialize\Tests\Models;

class UserPhone extends \Illuminate\Database\Eloquent\Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function userPhoneNote()
    {
        return $this->hasOne(UserPhoneNote::class);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $isPrimary
     * @return void
     */
    public function scopeMajor(\Illuminate\Database\Eloquent\Builder $query, bool $isPrimary)
    {
        $query->where('is_primary', '=', $isPrimary);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $pattern
     * @return void
     */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, string $pattern)
    {
        $query->where(function ($query) use ($pattern) {
            $query->where('phone', 'LIKE', "%$pattern%");
        });
    }
}
