<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Support\PostStatus;
use Livewire\Livewire;

/*
 * Authors submit, moderators decide. The public site shows an article only
 * once it is both approved and published.
 */

it('keeps the queue to moderators and administrators', function () {
    $this->get(route('admin.moderation'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.moderation'))->assertForbidden();

    $this->actingAs(User::factory()->moderator()->create());
    $this->get(route('admin.moderation'))->assertOk();

    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('admin.moderation'))->assertOk();
});

it('sends an author article to the queue instead of the site', function () {
    $this->actingAs(User::factory()->create());
    $category = Category::factory()->create();

    Livewire::test('pages::admin.posts')
        ->set('title', 'Community Clean-up')
        ->set('category_id', $category->id)
        ->set('body', 'A write-up of Saturday.')
        ->set('publish', true)
        ->call('chooseGalleryImage', 'gallery/x.webp')
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::firstWhere('title', 'Community Clean-up');

    expect($post->status)->toBe(PostStatus::Pending)
        // Ticking "publish" must not be a way around review.
        ->and($post->published_at)->toBeNull()
        ->and(Post::published()->count())->toBe(0);
});

it('publishes straight away for someone who can approve', function () {
    $this->actingAs(User::factory()->admin()->create());
    $category = Category::factory()->create();

    Livewire::test('pages::admin.posts')
        ->set('title', 'Staff Notice')
        ->set('category_id', $category->id)
        ->set('body', 'Straight to the site.')
        ->call('chooseGalleryImage', 'gallery/x.webp')
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::firstWhere('title', 'Staff Notice');

    expect($post->status)->toBe(PostStatus::Approved)
        ->and($post->published_at)->not->toBeNull();
});

it('approving puts the article on the site', function () {
    $this->actingAs(User::factory()->moderator()->create());
    $post = Post::factory()->awaitingReview()->create();

    Livewire::test('pages::admin.moderation')->call('approve', $post->id);

    $post->refresh();

    expect($post->status)->toBe(PostStatus::Approved)
        ->and($post->published_at)->not->toBeNull()
        ->and(Post::published()->whereKey($post->id)->exists())->toBeTrue();
});

it('rejecting needs a reason', function () {
    $this->actingAs(User::factory()->moderator()->create());
    $post = Post::factory()->awaitingReview()->create();

    Livewire::test('pages::admin.moderation')
        ->set('note', '')
        ->call('reject', $post->id)
        ->assertHasErrors(['note' => 'required']);

    expect($post->fresh()->status)->toBe(PostStatus::Pending);
});

it('rejecting records the reason and keeps the article off the site', function () {
    $moderator = User::factory()->moderator()->create();
    $this->actingAs($moderator);

    $post = Post::factory()->awaitingReview()->create(['published_at' => now()->subDay()]);

    Livewire::test('pages::admin.moderation')
        ->set('note', 'Reads like advertising copy, please rewrite.')
        ->call('reject', $post->id)
        ->assertHasNoErrors();

    $post->refresh();

    expect($post->status)->toBe(PostStatus::Rejected)
        ->and($post->review_note)->toBe('Reads like advertising copy, please rewrite.')
        ->and($post->reviewed_by)->toBe($moderator->id)
        ->and($post->published_at)->toBeNull();
});

it('hides pending and rejected articles from the public site', function () {
    Post::factory()->awaitingReview()->create(['title' => 'Waiting']);
    Post::factory()->rejected()->create(['title' => 'Turned down']);
    Post::factory()->create(['title' => 'Live one']);

    $this->get(route('news.index'))
        ->assertOk()
        ->assertSee('Live one')
        ->assertDontSee('Waiting')
        ->assertDontSee('Turned down');
});

it('will not serve a pending article by its own url', function () {
    $post = Post::factory()->awaitingReview()->create();

    $this->get(route('news.show', $post->slug))->assertNotFound();
});

it('shows an author only their own articles', function () {
    $author = User::factory()->create();
    $mine = Post::factory()->create(['submitted_by' => $author->id, 'title' => 'Mine']);
    Post::factory()->create(['title' => 'Someone elses']);

    $this->actingAs($author);

    Livewire::test('pages::admin.posts')
        ->assertSee('Mine')
        ->assertDontSee('Someone elses');

    expect($mine->submitted_by)->toBe($author->id);
});

it('stops an author editing an article that is not theirs', function () {
    $this->actingAs(User::factory()->create());
    $someoneElses = Post::factory()->create();

    Livewire::test('pages::admin.posts')
        ->call('edit', $someoneElses->id)
        ->assertForbidden();
});

it('stops an author editing their article once it is approved', function () {
    // Editing after approval would put unreviewed copy on the site.
    $author = User::factory()->create();
    $this->actingAs($author);

    $approved = Post::factory()->create(['submitted_by' => $author->id]);

    Livewire::test('pages::admin.posts')
        ->call('edit', $approved->id)
        ->assertForbidden();
});

it('lets an author revise an article that was sent back', function () {
    $author = User::factory()->create();
    $this->actingAs($author);

    $rejected = Post::factory()->rejected()->create(['submitted_by' => $author->id]);

    Livewire::test('pages::admin.posts')
        ->call('edit', $rejected->id)
        ->assertOk()
        ->set('body', 'Rewritten from scratch.')
        ->call('save')
        ->assertHasNoErrors();

    // Back into the queue, not straight back to where it was.
    expect($rejected->fresh()->status)->toBe(PostStatus::Pending);
});

it('refuses to save while posting is switched off', function () {
    Setting::putMany(['posting_enabled' => false]);

    $this->actingAs(User::factory()->create());
    $category = Category::factory()->create();

    Livewire::test('pages::admin.posts')
        ->set('title', 'Should not save')
        ->set('category_id', $category->id)
        ->set('body', 'Nope.')
        ->call('chooseGalleryImage', 'gallery/x.webp')
        ->call('save')
        ->assertForbidden();

    expect(Post::where('title', 'Should not save')->exists())->toBeFalse();
});
