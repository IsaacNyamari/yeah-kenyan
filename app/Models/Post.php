<?php

namespace App\Models;

use App\Concerns\ResolvesImageUrl;
use App\Support\PostStatus;
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
 * @property PostStatus $status
 * @property int|null $submitted_by
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $review_note
 * @property CarbonImmutable|null $published_at
 * @property-read string|null $image_url
 * @property-read Category $category
 * @property-read User|null $submitter
 * @property-read User|null $reviewer
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use ResolvesImageUrl;

    protected $fillable = [
        'category_id', 'title', 'slug', 'author', 'excerpt',
        'body', 'image', 'is_featured', 'is_trending', 'published_at',
        'status', 'submitted_by', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'status' => PostStatus::class,
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
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Limit the query to posts that are live.
     *
     * Publication and approval are separate gates: a moderator can approve an
     * article that is still scheduled, and an article can be published-dated
     * while it waits for review. The public site requires both.
     *
     * @param  Builder<Post>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Approved)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<Post>  $query
     */
    public function scopeAwaitingReview(Builder $query): void
    {
        $query->where('status', PostStatus::Pending);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
