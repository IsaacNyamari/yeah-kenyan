<?php

namespace App\Models;

use App\Support\NewsletterStatus;
use Carbon\CarbonImmutable;
use Database\Factories\NewsletterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $newsletter_template_id
 * @property int|null $created_by
 * @property string $subject
 * @property string|null $preheader
 * @property string $body
 * @property NewsletterStatus $status
 * @property CarbonImmutable|null $sent_at
 * @property-read NewsletterTemplate|null $template
 * @property-read HasMany<NewsletterSend, $this> $sends
 */
class Newsletter extends Model
{
    /** @use HasFactory<NewsletterFactory> */
    use HasFactory;

    protected $fillable = [
        'newsletter_template_id', 'created_by', 'subject', 'preheader', 'body', 'status', 'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => NewsletterStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<NewsletterTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NewsletterTemplate::class, 'newsletter_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<NewsletterSend, $this>
     */
    public function sends(): HasMany
    {
        return $this->hasMany(NewsletterSend::class);
    }

    public function isEditable(): bool
    {
        return $this->status === NewsletterStatus::Draft;
    }
}
