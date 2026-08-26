<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Alerts the office that a visitor submitted the contact form.
 */
class NewContactEnquiry extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ContactMessage $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New enquiry: '.$this->enquiry->subject,
            // Sent from the authenticated mailbox so SPF still passes; replying
            // goes straight to the visitor rather than back to ourselves.
            replyTo: [new Address($this->enquiry->email, $this->enquiry->name)],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contact.new-enquiry');
    }
}
