<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'status',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the post.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include published posts.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to filter by status.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by author.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeByAuthor(Builder $query, int $authorId): Builder
    {
        return $query->where('user_id', $authorId);
    }

    /**
     * Scope a query to filter by date range.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeDateRange(Builder $query, mixed $startDate = null, mixed $endDate = null): Builder
    {
        if ($startDate) {
            $query->where('published_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('published_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope a query to search by title or content.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%");
        });
    }
}
