<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Acknowledges the visitor's enquiry so they know it arrived.
 */
class ContactEnquiryReceived extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ContactMessage $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your enquiry — '.config('site.name'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contact.received');
    }
}
