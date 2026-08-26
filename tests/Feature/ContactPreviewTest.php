<?php

use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

/*
 * The admin preview and the public contact page render the same
 * x-site.contact-body component. These tests exist so that editing one
 * without the other shows up as a failure rather than as silent drift.
 */

beforeEach(function () {
    Setting::putMany([
        'contact_heading' => 'Talk To Our Team',
        'contact_intro' => 'We reply within one working day.',
        'contact_button_label' => 'Send Enquiry',
        'contact_success_message' => 'Got it, we will be in touch.',
        'contact_address' => 'Utawala, Nairobi, Kenya',
        'contact_email' => 'info@yeahkenyan.com',
        'contact_phone' => '+254 728 432784',
        'social_facebook' => 'https://facebook.com/yeahkenyan',
        'social_instagram' => '',
        'social_youtube' => '',
    ]);
});

/**
 * Everything the visitor should see on the live page.
 *
 * @return list<string>
 */
function contactLandmarks(): array
{
    return [
        'Get In Touch',
        'Talk To Our Team',
        'We reply within one working day.',
        'Contact Info',
        'Our Office',
        'Utawala, Nairobi, Kenya',
        'Email Us',
        'info@yeahkenyan.com',
        'Call Us',
        '+254 728 432784',
        'Follow Us',
        'Send Us A Message',
        'Your Name',
        'Your Email',
        'Subject',
        'Message',
        'Send Enquiry',
    ];
}

it('renders every landmark on the public contact page', function () {
    $response = $this->get(route('contact'))->assertOk();

    foreach (contactLandmarks() as $landmark) {
        $response->assertSee($landmark, escape: false);
    }
});

it('renders the same landmarks inside the admin preview', function () {
    $this->actingAs(User::factory()->create());

    $preview = Livewire::test('pages::admin.contact')->assertOk();

    foreach (contactLandmarks() as $landmark) {
        $preview->assertSee($landmark, escape: false);
    }
});

it('shows the intro paragraph on the live page', function () {
    // The intro was editable in the admin long before the public page rendered it.
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('We reply within one working day.');
});

it('omits the intro paragraph when it is blank', function () {
    Setting::putMany(['contact_intro' => '']);

    $this->get(route('contact'))
        ->assertOk()
        ->assertDontSee('We reply within one working day.');
});

it('reflects unsaved edits in the preview without touching the live page', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.contact')
        ->set('contact_heading', 'A Heading Not Yet Saved')
        ->assertSee('A Heading Not Yet Saved');

    auth()->logout();

    $this->get(route('contact'))
        ->assertOk()
        ->assertDontSee('A Heading Not Yet Saved')
        ->assertSee('Talk To Our Team');
});

it('keeps the preview form inert so it cannot post an enquiry', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.contact')
        ->assertDontSee('wire:submit="send"', escape: false)
        ->assertSee('readonly', escape: false);
});
