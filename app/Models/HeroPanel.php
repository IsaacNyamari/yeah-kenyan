<?php

namespace App\Models;

use App\Concerns\ResolvesImageUrl;
use App\Support\HeroPanelKind;
use Database\Factories\HeroPanelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A panel in the homepage hero.
 *
 * @property int $id
 * @property HeroPanelKind $kind
 * @property string $badge
 * @property string $text
 * @property string $image
 * @property int $sort_order
 * @property bool $is_published
 * @property-read string|null $image_url
 */
class HeroPanel extends Model
{
    /** @use HasFactory<HeroPanelFactory> */
    use HasFactory;

    use ResolvesImageUrl;

    protected $fillable = ['kind', 'badge', 'text', 'image', 'sort_order', 'is_published'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => HeroPanelKind::class,
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<HeroPanel>  $query
     */
    public function scopeOfKind(Builder $query, HeroPanelKind $kind): void
    {
        $query->where('kind', $kind)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<HeroPanel>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_published', true);
    }
}
