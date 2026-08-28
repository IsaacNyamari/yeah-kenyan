<?php

namespace App\Models;

use Database\Factories\NewsletterTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $html
 * @property bool $is_default
 */
class NewsletterTemplate extends Model
{
    /** @use HasFactory<NewsletterTemplateFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'html', 'is_default'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    /**
     * @return HasMany<Newsletter, $this>
     */
    public function newsletters(): HasMany
    {
        return $this->hasMany(Newsletter::class);
    }
}
