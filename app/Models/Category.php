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
        // Left untyped so the relation's Post query is inferred; a bare
        // Builder hint would hide the published() scope.
        $published = fn ($posts) => $posts->published();

        $query->withCount(['posts' => $published])->whereHas('posts', $published);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
