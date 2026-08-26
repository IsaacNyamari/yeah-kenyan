<?php

use App\Models\ContactMessage;
use App\Models\GalleryItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('locks the whole cms behind authentication', function (string $route) {
    auth()->logout();

    $this->get(route($route))->assertRedirect(route('login'));
})->with(['dashboard', 'admin.posts', 'admin.gallery', 'admin.services', 'admin.classes', 'admin.messages', 'admin.contact']);

it('shows headline counts on the dashboard', function () {
    Post::factory()->count(3)->create();
    Post::factory()->draft()->create();
    GalleryItem::factory()->count(2)->create();
    Subscriber::factory()->count(5)->create();
    ContactMessage::factory()->count(4)->create(['read_at' => null]);

    Livewire::test('pages::admin.dashboard')
        ->assertOk()
        ->assertSet('stats.posts', 4)
        ->assertSet('stats.published', 3)
        ->assertSet('stats.drafts', 1)
        ->assertSet('stats.gallery', 2)
        ->assertSet('stats.subscribers', 5)
        ->assertSet('stats.unread', 4);
});

it('buckets twelve months of activity for the chart', function () {
    Post::factory()->create(['published_at' => now()]);
    Post::factory()->create(['published_at' => now()->subMonths(2)]);

    $activity = Livewire::test('pages::admin.dashboard')->get('monthlyActivity');

    expect($activity)->toHaveCount(12)
        ->and(collect($activity)->sum('posts'))->toBe(2);
});

it('creates a service page and auto-slugs the title', function () {
    Livewire::test('pages::admin.services')
        ->set('title', 'Drone Coverage')
        ->assertSet('slug', 'drone-coverage')
        ->set('nav', 'Drone Coverage')
        ->set('heading', 'Aerial Coverage That Lands')
        ->set('intro', 'We fly licensed drones for events across Kenya.')
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::firstWhere('slug', 'drone-coverage');

    expect($page)->not->toBeNull()
        ->and($page->type)->toBe(Page::TYPE_SERVICE)
        ->and($page->is_published)->toBeTrue();
});

it('does not re-slug an existing page when the title is edited', function () {
    $page = Page::factory()->create(['slug' => 'original-slug']);

    Livewire::test('pages::admin.services')
        ->call('edit', $page->id)
        ->set('title', 'A Completely New Title')
        ->assertSet('slug', 'original-slug');
});

it('rejects a duplicate slug', function () {
    Page::factory()->create(['slug' => 'taken']);

    Livewire::test('pages::admin.services')
        ->set('title', 'Something')
        ->set('slug', 'taken')
        ->set('nav', 'Something')
        ->set('heading', 'Heading')
        ->set('intro', 'Intro copy here.')
        ->call('save')
        ->assertHasErrors('slug');
});

it('builds nested sections and drops the blank rows', function () {
    Livewire::test('pages::admin.services')
        ->set('title', 'Sound Hire')
        ->set('nav', 'Sound Hire')
        ->set('heading', 'Sound Hire')
        ->set('intro', 'Line array systems for any venue.')
        ->call('addSection')
        ->set('sections.0.heading', 'Why Choose Us')
        ->set('sections.0.items.0.label', 'Line array')
        ->set('sections.0.items.0.text', 'Even coverage front to back.')
        ->call('addItem', 0)
        ->call('save')
        ->assertHasNoErrors();

    $sections = Page::firstWhere('slug', 'sound-hire')->sections;

    expect($sections)->toHaveCount(1)
        ->and($sections[0]['heading'])->toBe('Why Choose Us')
        ->and($sections[0]['items'])->toHaveCount(1);
});

it('toggles a page between live and hidden', function () {
    $page = Page::factory()->create(['is_published' => true]);

    Livewire::test('pages::admin.services')->call('togglePublished', $page->id);

    expect($page->refresh()->is_published)->toBeFalse();
});

it('reorders pages with the move controls', function () {
    $first = Page::factory()->create(['sort_order' => 0, 'slug' => 'first']);
    $second = Page::factory()->create(['sort_order' => 1, 'slug' => 'second']);

    Livewire::test('pages::admin.services')->call('moveDown', $first->id);

    expect($first->refresh()->sort_order)->toBe(1)
        ->and($second->refresh()->sort_order)->toBe(0);
});

it('keeps services and classes in separate lists', function () {
    Page::factory()->create(['slug' => 'a-service']);
    Page::factory()->onlineClass()->create(['slug' => 'a-class']);

    Livewire::test('pages::admin.services')->assertSee('a-service')->assertDontSee('a-class');
    Livewire::test('pages::admin.classes')->assertSee('a-class')->assertDontSee('a-service');
});

it('optimizes the hero image when a page is saved', function () {
    Storage::fake('public');

    Livewire::test('pages::admin.services')
        ->set('title', 'Lighting')
        ->set('nav', 'Lighting')
        ->set('heading', 'Lighting')
        ->set('intro', 'Stage and camera lighting.')
        ->set('photo', UploadedFile::fake()->image('rig.jpg', 3000, 2000))
        ->call('save')
        ->assertHasNoErrors();

    $image = Page::firstWhere('slug', 'lighting')->image;

    expect($image)->toStartWith('pages/')->toEndWith('.webp');

    Storage::disk('public')->assertExists($image);
});

it('marks a message read when it is opened', function () {
    $message = ContactMessage::factory()->create(['read_at' => null]);

    Livewire::test('pages::admin.messages')
        ->call('select', $message->id)
        ->assertSet('selectedId', $message->id);

    expect($message->refresh()->read_at)->not->toBeNull();
});

it('marks every message read at once', function () {
    ContactMessage::factory()->count(3)->create(['read_at' => null]);

    Livewire::test('pages::admin.messages')->call('markAllRead');

    expect(ContactMessage::whereNull('read_at')->count())->toBe(0);
});

it('filters the inbox down to unread', function () {
    $unread = ContactMessage::factory()->create(['subject' => 'Needs a reply', 'read_at' => null]);
    $read = ContactMessage::factory()->create(['subject' => 'Already handled', 'read_at' => now()]);

    Livewire::test('pages::admin.messages')
        ->set('filter', 'unread')
        ->assertSee($unread->subject)
        ->assertDontSee($read->subject);
});

it('saves contact settings and surfaces them on the public page', function () {
    Livewire::test('pages::admin.contact')
        ->set('contact_heading', 'Talk To Our Team')
        ->set('contact_intro', 'We reply within a day.')
        ->set('contact_button_label', 'Send Enquiry')
        ->set('contact_success_message', 'Got it — we will be in touch.')
        ->set('contact_address', 'Utawala, Nairobi')
        ->set('contact_email', 'hello@yeahkenyan.com')
        ->set('contact_phone', '+254 700 000000')
        ->set('social_facebook', 'https://facebook.com/yeahkenyan')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('contact_heading'))->toBe('Talk To Our Team');

    auth()->logout();

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Talk To Our Team')
        ->assertSee('Send Enquiry')
        ->assertSee('hello@yeahkenyan.com');
});

it('rejects an invalid contact email and a malformed social url', function () {
    Livewire::test('pages::admin.contact')
        ->set('contact_email', 'not-an-email')
        ->set('social_facebook', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['contact_email', 'social_facebook']);
});

/*
 * $wire.upload() is Livewire's own file-upload JS API. A component action
 * named upload() gets shadowed by it, so wire:submit calls the uploader with
 * no arguments and the browser throws before the action ever runs. These pin
 * the working name and the optimisation that follows.
 */
it('uploads gallery images through an action that does not collide with livewire', function () {
    Storage::fake('public');

    Livewire::test('pages::admin.gallery')
        ->set('collection', 'weddings')
        ->set('title', 'Naivasha ceremony')
        ->set('photos', [
            UploadedFile::fake()->image('one.jpg', 2400, 1600),
            UploadedFile::fake()->image('two.jpg', 800, 600),
        ])
        ->call('uploadImages')
        ->assertHasNoErrors()
        ->assertSet('photos', []);

    $items = GalleryItem::orderBy('id')->get();

    expect($items)->toHaveCount(2)
        ->and($items->first()->collection)->toBe('weddings')
        ->and($items->first()->title)->toBe('Naivasha ceremony');

    foreach ($items as $item) {
        expect($item->image)->toStartWith('gallery/')->toEndWith('.webp');
        Storage::disk('public')->assertExists($item->image);
    }
});

it('rejects a non-image upload to the gallery', function () {
    Storage::fake('public');

    Livewire::test('pages::admin.gallery')
        ->set('photos', [UploadedFile::fake()->create('notes.pdf', 100)])
        ->call('uploadImages')
        ->assertHasErrors('photos.0');

    expect(GalleryItem::count())->toBe(0);
});
