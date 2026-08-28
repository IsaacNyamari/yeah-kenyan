<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recipient's copy of one issue.
 *
 * @property int $id
 * @property int $newsletter_id
 * @property int $subscriber_id
 * @property CarbonImmutable|null $sent_at
 * @property string|null $failure
 * @property-read Subscriber|null $subscriber
 */
class NewsletterSend extends Model
{
    protected $fillable = ['newsletter_id', 'subscriber_id', 'sent_at', 'failure'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Subscriber, $this>
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    /**
     * @return BelongsTo<Newsletter, $this>
     */
    public function newsletter(): BelongsTo
    {
        return $this->belongsTo(Newsletter::class);
    }
}
