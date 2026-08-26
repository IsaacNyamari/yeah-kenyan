<?php

use App\Models\ContactMessage;
use App\Models\GalleryItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('stages an action without performing it', function () {
    $page = Page::factory()->create();

    Livewire::test('pages::admin.services')
        ->call('confirmAction', 'delete', $page->id, ['heading' => 'Delete this page?'])
        ->assertSet('pendingAction.method', 'delete')
        ->assertSet('pendingAction.id', $page->id)
        ->assertSet('pendingAction.heading', 'Delete this page?');

    // Nothing happens until the modal is confirmed.
    expect(Page::find($page->id))->not->toBeNull();
});

it('performs the action once confirmed', function () {
    $page = Page::factory()->create();

    Livewire::test('pages::admin.services')
        ->call('confirmAction', 'delete', $page->id)
        ->call('runPendingAction')
        ->assertSet('pendingAction', null);

    expect(Page::find($page->id))->toBeNull();
});

it('leaves the record alone when cancelled', function () {
    $page = Page::factory()->create();

    Livewire::test('pages::admin.services')
        ->call('confirmAction', 'delete', $page->id)
        ->call('cancelPendingAction')
        ->assertSet('pendingAction', null);

    expect(Page::find($page->id))->not->toBeNull();
});

it('does nothing when confirming with no action staged', function () {
    Livewire::test('pages::admin.services')
        ->call('runPendingAction')
        ->assertOk()
        ->assertSet('pendingAction', null);
});

/*
 * pendingAction is a public Livewire property, so the browser can write to it.
 * Without the allow-list, a crafted request could name any public method on the
 * component and have runPendingAction() invoke it.
 */
it('refuses to stage a method that is not on the allow-list', function () {
    Livewire::test('pages::admin.services')
        ->call('confirmAction', 'resetForm')
        ->assertForbidden();
});

it('refuses to run a tampered method name', function () {
    $page = Page::factory()->create();

    Livewire::test('pages::admin.services')
        ->call('confirmAction', 'delete', $page->id)
        ->set('pendingAction.method', 'togglePublished')
        ->call('runPendingAction')
        ->assertForbidden();

    expect($page->refresh()->is_published)->toBeTrue();
});

it('confirms article deletion', function () {
    $post = Post::factory()->create();

    Livewire::test('pages::admin.posts')
        ->call('confirmAction', 'delete', $post->id)
        ->assertSet('pendingAction.method', 'delete')
        ->call('runPendingAction');

    expect(Post::find($post->id))->toBeNull();
});

it('confirms gallery image removal', function () {
    $item = GalleryItem::factory()->create();

    Livewire::test('pages::admin.gallery')
        ->call('confirmAction', 'delete', $item->id)
        ->call('runPendingAction');

    expect(GalleryItem::find($item->id))->toBeNull();
});

it('confirms message deletion and bulk mark-as-read', function () {
    $message = ContactMessage::factory()->create();
    ContactMessage::factory()->count(2)->create(['read_at' => null]);

    Livewire::test('pages::admin.messages')
        ->call('confirmAction', 'markAllRead', null, ['variant' => 'info'])
        ->assertSet('pendingAction.variant', 'info')
        ->call('runPendingAction');

    expect(ContactMessage::whereNull('read_at')->count())->toBe(0);

    Livewire::test('pages::admin.messages')
        ->call('confirmAction', 'delete', $message->id)
        ->call('runPendingAction');

    expect(ContactMessage::find($message->id))->toBeNull();
});

it('no longer uses the browser confirm dialog anywhere', function (string $component) {
    Livewire::test($component)->assertDontSee('wire:confirm', escape: false);
})->with([
    'pages::admin.posts',
    'pages::admin.gallery',
    'pages::admin.messages',
    'pages::admin.services',
    'pages::admin.classes',
]);
