<?php

use App\Mail\ContactEnquiryReceived;
use App\Mail\NewContactEnquiry;
use App\Models\ContactMessage;
use App\Models\Setting;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.site')] #[Title('Contact Us')] class extends Component {
    public string $name = '';

    public string $email = '';

    public string $subject = '';

    public string $message = '';

    /**
     * Submissions allowed from one address per window.
     */
    private const MAX_ATTEMPTS = 3;

    private const DECAY_SECONDS = 600;

    public function send(): void
    {
        // The acknowledgement goes to an address the sender types in, so an
        // unthrottled form is a way to have us mail strangers on demand.
        $key = 'contact-form:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $this->addError('message', sprintf(
                'Too many messages sent. Please try again in %d minutes.',
                ceil(RateLimiter::availableIn($key) / 60),
            ));

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        RateLimiter::hit($key, self::DECAY_SECONDS);

        $enquiry = ContactMessage::create($validated);

        $this->notify($enquiry);

        $this->reset('name', 'email', 'subject', 'message');

        Flux::toast(
            variant: 'success',
            heading: 'Message sent',
            text: Setting::get('contact_success_message', 'Thank you for reaching out. We will get back to you shortly.'),
        );
    }

    /**
     * Queue the office alert and the sender's acknowledgement.
     *
     * The enquiry is already saved at this point, so a mail failure must not
     * surface as a submission failure — it is logged and swallowed instead.
     */
    private function notify(ContactMessage $enquiry): void
    {
        try {
            Mail::to(config('mail.enquiries_to', config('mail.from.address')))
                ->send(new NewContactEnquiry($enquiry));

            Mail::to($enquiry->email)->send(new ContactEnquiryReceived($enquiry));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}; ?>

<div>
    <x-site.contact-body
        :heading="\App\Models\Setting::get('contact_heading', 'Contact Us For Any Queries')"
        :intro="\App\Models\Setting::get('contact_intro')"
        :address="\App\Models\Setting::get('contact_address')"
        :email="\App\Models\Setting::get('contact_email')"
        :phone="\App\Models\Setting::get('contact_phone')"
        :facebook="\App\Models\Setting::get('social_facebook')"
        :instagram="\App\Models\Setting::get('social_instagram')"
        :youtube="\App\Models\Setting::get('social_youtube')"
        :button-label="\App\Models\Setting::get('contact_button_label', 'Send Message')"
    />
</div>
