<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $email
 * @property string|null $name
 * @property CarbonImmutable|null $unsubscribed_at
 * @property string $token
 * @property CarbonImmutable|null $created_at
 */
class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    // unsubscribed_at is fillable on purpose: every path that honours an
    // unsubscribe request writes it through update(), and leaving it out made
    // all of them silently do nothing.
    protected $fillable = ['email', 'name', 'unsubscribed_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['unsubscribed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Every subscriber needs a token from the moment they exist, or the
        // unsubscribe link in their first newsletter has nothing to resolve.
        static::creating(function (self $subscriber): void {
            $subscriber->token ??= Str::random(48);
        });
    }

    /**
     * @param  Builder<Subscriber>  $query
     */
    public function scopeSubscribed(Builder $query): void
    {
        $query->whereNull('unsubscribed_at');
    }

    public function isSubscribed(): bool
    {
        return $this->unsubscribed_at === null;
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }
}
