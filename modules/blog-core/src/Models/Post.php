<?php

namespace Liberu\Blog\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property string $status
 * @property Carbon|null $published_at
 */
class Post extends Model
{
    protected $table = 'module_blog_posts';

    /**
     * @var list<string>
     */
    protected $fillable = ['user_id', 'title', 'slug', 'body', 'status', 'published_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(config('blog.author_model'), 'user_id');
    }
}
