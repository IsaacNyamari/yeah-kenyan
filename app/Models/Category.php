<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Categories that have something live to show.
     *
     * Counting published posts rather than all posts matters: a category
     * holding only drafts would otherwise appear in the menu and then return
     * an empty list when a visitor clicked it.
     *
     * @param  Builder<Category>  $query
     */
    public function scopeWithPublishedPosts(Builder $query): void
    {
        $query
            ->withCount(['posts' => fn (Builder $posts) => $posts->published()])
            ->whereHas('posts', fn (Builder $posts) => $posts->published());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
