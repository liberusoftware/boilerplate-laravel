<?php

namespace Liberu\Foundation\Organizations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Organization extends Model
{
    protected $fillable = ['name', 'slug', 'owner_id', 'status', 'archived_at'];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}
