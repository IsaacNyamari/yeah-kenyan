<?php

namespace App\Services;

use App\Mail\NewsletterIssue;
use App\Models\Newsletter;
use App\Models\NewsletterSend;
use App\Models\Subscriber;
use App\Support\NewsletterStatus;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends an issue a chunk at a time.
 *
 * Shared hosting has no queue worker, and mailing a few hundred people inside
 * one request would hit the execution limit halfway through with no record of
 * who had already been written to. So the recipient list is written down first
 * and worked through in small batches across several requests: each batch is
 * short enough to finish, and the row per recipient is what makes a repeated
 * or overlapping batch harmless.
 */
class NewsletterDispatcher
{
    /**
     * Recipients per request. Small enough to finish comfortably inside a
     * default 30-second limit on a slow SMTP connection.
     */
    public const CHUNK = 20;

    /**
     * Write down who this issue is going to.
     *
     * Called once, at the moment sending starts. Anyone who subscribes later
     * is deliberately not added: they did not sign up for an issue that was
     * already going out.
     *
     * @return int the number of recipients
     */
    public function prepare(Newsletter $newsletter): int
    {
        $rows = Subscriber::subscribed()
            ->pluck('id')
            ->map(fn (int $id): array => [
                'newsletter_id' => $newsletter->getKey(),
                'subscriber_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if ($rows !== []) {
            // Ignores duplicates, so resuming a half-finished send does not
            // queue anybody a second time.
            foreach (array_chunk($rows, 500) as $batch) {
                NewsletterSend::insertOrIgnore($batch);
            }
        }

        $newsletter->update(['status' => NewsletterStatus::Sending]);

        return count($rows);
    }

    /**
     * Send the next batch.
     *
     * @return array{sent: int, failed: int, remaining: int, done: bool}
     */
    public function sendChunk(Newsletter $newsletter, int $limit = self::CHUNK): array
    {
        $pending = NewsletterSend::with('subscriber')
            ->where('newsletter_id', $newsletter->getKey())
            ->whereNull('sent_at')
            ->whereNull('failure')
            ->limit($limit)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($pending as $send) {
            $subscriber = $send->subscriber;

            // Someone who unsubscribed after the list was written down must
            // not receive the issue anyway.
            if (! $subscriber instanceof Subscriber || ! $subscriber->isSubscribed()) {
                $send->update(['failure' => 'Unsubscribed before this issue went out']);
                $failed++;

                continue;
            }

            try {
                Mail::to($subscriber->email)->send(new NewsletterIssue($newsletter, $subscriber));

                $send->update(['sent_at' => now(), 'failure' => null]);
                $sent++;
            } catch (Throwable $e) {
                // Recorded against the recipient rather than thrown, so one bad
                // address cannot stop the rest of the list.
                $send->update(['failure' => str($e->getMessage())->limit(200)->toString()]);
                $failed++;
            }
        }

        $progress = $this->progress($newsletter);

        if ($progress['remaining'] === 0) {
            $newsletter->update([
                'status' => NewsletterStatus::Sent,
                'sent_at' => $newsletter->sent_at ?? now(),
            ]);
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'remaining' => $progress['remaining'],
            'done' => $progress['remaining'] === 0,
        ];
    }

    /**
     * @return array{total: int, sent: int, failed: int, remaining: int}
     */
    public function progress(Newsletter $newsletter): array
    {
        $sends = NewsletterSend::where('newsletter_id', $newsletter->getKey());

        $total = (clone $sends)->count();
        $sent = (clone $sends)->whereNotNull('sent_at')->count();
        $failed = (clone $sends)->whereNull('sent_at')->whereNotNull('failure')->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'remaining' => max(0, $total - $sent - $failed),
        ];
    }

    /**
     * Send one copy to an arbitrary address for checking.
     *
     * Deliberately outside the recipient list: a test send must not mark
     * anybody as having received the issue.
     */
    public function sendTest(Newsletter $newsletter, string $email): void
    {
        $preview = new Subscriber([
            'email' => $email,
            'name' => 'Preview',
        ]);

        $preview->token = 'preview';

        Mail::to($email)->send(new NewsletterIssue($newsletter, $preview));
    }
}
