<?php

use App\Mail\ContactEnquiryReceived;
use App\Mail\NewContactEnquiry;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Providers\SettingsServiceProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    RateLimiter::clear('contact-form:127.0.0.1');
});

function submitEnquiry(array $overrides = []): Testable
{
    return Livewire::test('pages::contact')
        ->set('name', $overrides['name'] ?? 'Jane Wanjiru')
        ->set('email', $overrides['email'] ?? 'jane@example.com')
        ->set('subject', $overrides['subject'] ?? 'Wedding coverage')
        ->set('message', $overrides['message'] ?? 'We need videography for a wedding in Naivasha next month.')
        ->call('send');
}

it('alerts the office when an enquiry arrives', function () {
    submitEnquiry()->assertHasNoErrors();

    Mail::assertQueued(NewContactEnquiry::class, function (NewContactEnquiry $mail): bool {
        return $mail->hasTo(config('mail.enquiries_to'))
            && $mail->enquiry->email === 'jane@example.com';
    });
});

it('acknowledges the enquiry to the sender', function () {
    submitEnquiry()->assertHasNoErrors();

    Mail::assertQueued(ContactEnquiryReceived::class, fn (ContactEnquiryReceived $mail): bool => $mail->hasTo('jane@example.com'));
});

it('points replies at the visitor rather than back at ourselves', function () {
    $enquiry = ContactMessage::factory()->create(['name' => 'Jane Wanjiru', 'email' => 'jane@example.com']);

    $envelope = (new NewContactEnquiry($enquiry))->envelope();

    expect($envelope->replyTo[0]->address)->toBe('jane@example.com')
        ->and($envelope->subject)->toContain($enquiry->subject);
});

it('renders both emails without error', function () {
    $enquiry = ContactMessage::factory()->create(['name' => 'Jane Wanjiru']);

    expect((new NewContactEnquiry($enquiry))->render())->toContain($enquiry->subject)
        ->and((new ContactEnquiryReceived($enquiry))->render())->toContain('Jane Wanjiru');
});

it('queues the mail so the form does not wait on smtp', function () {
    expect(new NewContactEnquiry(ContactMessage::factory()->create()))
        ->toBeInstanceOf(ShouldQueue::class);
});

it('still records the enquiry when mail fails', function () {
    // The record is saved before mail is attempted, so a broken mail server
    // must not present itself to the visitor as a failed submission.
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

    submitEnquiry()->assertHasNoErrors();

    expect(ContactMessage::where('email', 'jane@example.com')->exists())->toBeTrue();
});

it('throttles repeated submissions from one address', function () {
    foreach (range(1, 3) as $i) {
        submitEnquiry(['email' => "sender{$i}@example.com"])->assertHasNoErrors();
    }

    submitEnquiry(['email' => 'sender4@example.com'])->assertHasErrors('message');

    expect(ContactMessage::count())->toBe(3);
});

it('does not mail anything once throttled', function () {
    foreach (range(1, 3) as $i) {
        submitEnquiry(['email' => "sender{$i}@example.com"]);
    }

    Mail::fake();

    submitEnquiry(['email' => 'blocked@example.com']);

    Mail::assertNothingQueued();
});

it('validates before consuming a rate-limit attempt', function () {
    Livewire::test('pages::contact')
        ->set('name', '')
        ->set('email', 'nope')
        ->call('send')
        ->assertHasErrors(['name', 'email']);

    // A rejected submission should not count against the sender's allowance.
    submitEnquiry()->assertHasNoErrors();

    Mail::assertQueued(NewContactEnquiry::class);
});

/*
 * An enquiry must reach two places: the inbox on the dashboard, and the
 * address configured under Settings -> Mail. The database write happens first
 * so a mail failure can never lose the enquiry.
 */
it('delivers an enquiry to the configured address and the messages table', function () {
    Setting::putMany(['mail_enquiries_to' => 'admin@yeahkenyan.com']);
    (new SettingsServiceProvider(app()))->boot();

    submitEnquiry(['name' => 'Grace Achieng', 'email' => 'grace@example.com'])->assertHasNoErrors();

    // 1. Stored for the dashboard.
    $enquiry = ContactMessage::firstWhere('email', 'grace@example.com');

    expect($enquiry)->not->toBeNull()
        ->and($enquiry->name)->toBe('Grace Achieng')
        ->and($enquiry->read_at)->toBeNull();

    // 2. Emailed to the administrator.
    Mail::assertQueued(
        NewContactEnquiry::class,
        fn (NewContactEnquiry $mail): bool => $mail->hasTo('admin@yeahkenyan.com')
    );
});

it('still records the enquiry when the address is misconfigured', function () {
    Setting::putMany(['mail_enquiries_to' => '']);
    (new SettingsServiceProvider(app()))->boot();

    submitEnquiry(['email' => 'nobody@example.com'])->assertHasNoErrors();

    expect(ContactMessage::where('email', 'nobody@example.com')->exists())->toBeTrue();
});
