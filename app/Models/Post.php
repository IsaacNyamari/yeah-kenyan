<?php

namespace App\Models;

use App\Concerns\ResolvesImageUrl;
use Carbon\CarbonImmutable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $category_id
 * @property string $title
 * @property string $slug
 * @property string $author
 * @property string|null $excerpt
 * @property string $body
 * @property string|null $image
 * @property bool $is_featured
 * @property bool $is_trending
 * @property CarbonImmutable|null $published_at
 * @property-read string|null $image_url
 * @property-read Category $category
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use ResolvesImageUrl;

    protected $fillable = [
        'category_id', 'title', 'slug', 'author', 'excerpt',
        'body', 'image', 'is_featured', 'is_trending', 'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Limit the query to posts that are live.
     *
     * @param  Builder<Post>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
