<?php

namespace App\Models;

use App\Concerns\ResolvesImageUrl;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A service or online-class landing page, editable through the CMS.
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    use ResolvesImageUrl;

    public const TYPE_SERVICE = 'service';

    public const TYPE_CLASS = 'class';

    protected $fillable = [
        'slug', 'type', 'nav', 'title', 'heading', 'cta',
        'image', 'intro', 'sections', 'footnotes', 'sort_order', 'is_published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'footnotes' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopeServices(Builder $query): void
    {
        $query->where('type', self::TYPE_SERVICE);
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopeClasses(Builder $query): void
    {
        $query->where('type', self::TYPE_CLASS);
    }

    /**
     * Published pages of a type, ordered for menus.
     *
     * @return Collection<int, Page>
     */
    public static function navigation(string $type): Collection
    {
        return self::query()->published()->where('type', $type)->orderBy('sort_order')->get();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
