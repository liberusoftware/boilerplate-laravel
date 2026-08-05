<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\Fixtures\Factories\GroupFactory;

/**
 * The second searchable type for the host's search composition tests.
 *
 * See {@see Post} for why these live here rather than in a module.
 */
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    protected $table = 'search_fixture_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'type',
        'is_active',
    ];

    protected static function newFactory(): GroupFactory
    {
        return GroupFactory::new();
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'owner_id');
    }

    /**
     * @param  Builder<Group>  $query
     * @return Builder<Group>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * @param  Builder<Group>  $query
     * @return Builder<Group>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Group>  $query
     * @return Builder<Group>
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * @param  Builder<Group>  $query
     * @return Builder<Group>
     */
    public function scopeByOwner(Builder $query, int $ownerId): Builder
    {
        return $query->where('owner_id', $ownerId);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
