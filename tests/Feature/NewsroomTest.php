<?php

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\Subscriber;
use Livewire\Livewire;

it('lists only published posts', function () {
    $live = Post::factory()->create(['title' => 'Published Story']);
    $draft = Post::factory()->draft()->create(['title' => 'Draft Story']);

    $this->get(route('news.index'))
        ->assertOk()
        ->assertSee($live->title)
        ->assertDontSee($draft->title);
});

it('filters the news list by category without a page reload', function () {
    $sports = Category::factory()->create(['name' => 'Sports', 'slug' => 'sports']);
    $politics = Category::factory()->create(['name' => 'Politics', 'slug' => 'politics']);

    $sportsPost = Post::factory()->for($sports)->create(['title' => 'Harambee Stars Return']);
    $politicsPost = Post::factory()->for($politics)->create(['title' => 'County Budget Passed']);

    Livewire::test('pages::news.index')
        ->call('filterBy', 'sports')
        ->assertSee($sportsPost->title)
        ->assertDontSee($politicsPost->title);
});

it('searches posts by title', function () {
    $match = Post::factory()->create(['title' => 'Drone Coverage in Nakuru']);
    $other = Post::factory()->create(['title' => 'Sound System Review']);

    Livewire::test('pages::news.index')
        ->set('search', 'Drone')
        ->assertSee($match->title)
        ->assertDontSee($other->title);
});

it('shows a published article and hides drafts', function () {
    $post = Post::factory()->create(['slug' => 'live-stream-recap']);

    $this->get(route('news.show', $post->slug))->assertOk()->assertSee($post->title);

    $draft = Post::factory()->draft()->create(['slug' => 'unfinished']);

    $this->get(route('news.show', $draft->slug))->assertNotFound();
});

it('stores a contact message and confirms with a toast', function () {
    Livewire::test('pages::contact')
        ->set('name', 'Jane Wanjiru')
        ->set('email', 'jane@example.com')
        ->set('subject', 'Wedding coverage')
        ->set('message', 'We need videography for a wedding in Naivasha next month.')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('name', '');

    expect(ContactMessage::where('email', 'jane@example.com')->exists())->toBeTrue();
});

it('validates the contact form', function () {
    Livewire::test('pages::contact')
        ->set('name', '')
        ->set('email', 'not-an-email')
        ->set('subject', '')
        ->set('message', 'short')
        ->call('send')
        ->assertHasErrors(['name', 'email', 'subject', 'message']);

    expect(ContactMessage::count())->toBe(0);
});

it('subscribes an email to the newsletter once', function () {
    Livewire::test('pages::site.newsletter')
        ->set('email', 'reader@example.com')
        ->call('subscribe')
        ->assertHasNoErrors();

    expect(Subscriber::where('email', 'reader@example.com')->exists())->toBeTrue();

    Livewire::test('pages::site.newsletter')
        ->set('email', 'reader@example.com')
        ->call('subscribe')
        ->assertHasErrors('email');

    expect(Subscriber::count())->toBe(1);
});
