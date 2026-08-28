<?php

namespace App\Mail;

use App\Models\Newsletter;
use App\Models\Subscriber;
use App\Services\NewsletterRenderer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * One issue, addressed to one subscriber.
 *
 * Not queued: shared hosting has no worker running, so a queued newsletter
 * would sit in the jobs table forever. Sending is chunked across requests by
 * the send screen instead.
 */
class NewsletterIssue extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly Newsletter $newsletter,
        public readonly Subscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->newsletter->subject);
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            // Lets a mail client offer one-click unsubscribe, which keeps the
            // list out of spam folders better than a footer link alone.
            'List-Unsubscribe' => '<'.app(NewsletterRenderer::class)->unsubscribeUrl($this->subscriber).'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        $renderer = app(NewsletterRenderer::class);

        return new Content(
            htmlString: $renderer->render($this->newsletter, $this->subscriber),
            text: 'mail.newsletter.plain',
            with: ['plain' => $renderer->renderText($this->newsletter, $this->subscriber)],
        );
    }
}
