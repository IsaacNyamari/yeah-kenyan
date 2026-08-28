<?php

use App\Models\Category;
use App\Models\GalleryItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
 * Editors can attach an image already in the gallery instead of uploading a
 * second copy. The path is shared rather than duplicated, which is the point —
 * and also why deleting either record has to leave the file alone while
 * anything else still points at it.
 */

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    Storage::fake('public');

    // Explicit collection so the filtering test is not confused by the factory default.
    $this->galleryItem = GalleryItem::factory()->create([
        'image' => 'gallery/shared-photo.webp',
        'collection' => 'corporate',
    ]);
    Storage::disk('public')->put('gallery/shared-photo.webp', 'binary');
});

it('attaches a gallery image to a testimonial without copying the file', function () {
    Livewire::test('pages::admin.testimonials')
        ->set('client', 'Church Events')
        ->set('quote', 'They handled the whole production end to end.')
        ->call('chooseGalleryImage', 'gallery/shared-photo.webp')
        ->assertSet('galleryImage', 'gallery/shared-photo.webp')
        ->call('save')
        ->assertHasNoErrors();

    expect(Testimonial::firstWhere('client', 'Church Events')->image)
        ->toBe('gallery/shared-photo.webp');

    // One file, two records pointing at it.
    expect(Storage::disk('public')->files('gallery'))->toHaveCount(1);
});

it('attaches a gallery image to an article, so no cover upload is required', function () {
    $category = Category::factory()->create();

    Livewire::test('pages::admin.posts')
        ->set('title', 'Crossover Night')
        ->set('category_id', $category->id)
        ->set('body', 'Coverage of the year-end service.')
        ->call('chooseGalleryImage', 'gallery/shared-photo.webp')
        ->call('save')
        ->assertHasNoErrors();

    expect(Post::firstWhere('title', 'Crossover Night')->image)->toBe('gallery/shared-photo.webp');
});

it('attaches a gallery image to a service page', function () {
    Livewire::test('pages::admin.services')
        ->set('title', 'Drone Coverage')
        ->set('nav', 'Drone Coverage')
        ->set('heading', 'Aerial Coverage')
        ->set('intro', 'Licensed drone pilots for events across Kenya.')
        ->call('chooseGalleryImage', 'gallery/shared-photo.webp')
        ->call('save')
        ->assertHasNoErrors();

    expect(Page::firstWhere('slug', 'drone-coverage')->image)->toBe('gallery/shared-photo.webp');
});

it('treats an upload and a gallery pick as alternatives', function () {
    $component = Livewire::test('pages::admin.testimonials')
        ->call('chooseGalleryImage', 'gallery/shared-photo.webp')
        ->assertSet('galleryImage', 'gallery/shared-photo.webp');

    // Uploading afterwards should supersede the pick, not sit alongside it.
    $component->set('photo', UploadedFile::fake()->image('portrait.jpg'))
        ->assertSet('galleryImage', null);
});

it('keeps the file when a gallery item still in use is deleted', function () {
    Post::factory()->create(['image' => 'gallery/shared-photo.webp']);

    Livewire::test('pages::admin.gallery')
        ->call('confirmAction', 'delete', $this->galleryItem->id)
        ->call('runPendingAction');

    expect(GalleryItem::find($this->galleryItem->id))->toBeNull()
        // The article still points at it, so the file must survive.
        ->and(Storage::disk('public')->exists('gallery/shared-photo.webp'))->toBeTrue();
});

it('keeps the file when an article using a gallery image is deleted', function () {
    $post = Post::factory()->create(['image' => 'gallery/shared-photo.webp']);

    Livewire::test('pages::admin.posts')
        ->call('confirmAction', 'delete', $post->id)
        ->call('runPendingAction');

    expect(Post::find($post->id))->toBeNull()
        ->and(Storage::disk('public')->exists('gallery/shared-photo.webp'))->toBeTrue();
});

it('removes the file when the last record using it goes', function () {
    Storage::disk('public')->put('posts/only-here.webp', 'binary');
    $post = Post::factory()->create(['image' => 'posts/only-here.webp']);

    Livewire::test('pages::admin.posts')
        ->call('confirmAction', 'delete', $post->id)
        ->call('runPendingAction');

    expect(Storage::disk('public')->exists('posts/only-here.webp'))->toBeFalse();
});

it('never deletes images migrated from the legacy site', function () {
    // These live under public/ and are shared by the seeded pages.
    $post = Post::factory()->create(['image' => 'uploads/wedding-1.jpg']);

    Livewire::test('pages::admin.posts')
        ->call('confirmAction', 'delete', $post->id)
        ->call('runPendingAction');

    expect(Post::find($post->id))->toBeNull()
        ->and(is_file(public_path('uploads/wedding-1.jpg')))->toBeTrue();
});

it('lists gallery images to choose from, filtered by collection', function () {
    GalleryItem::factory()->create(['image' => 'gallery/a.webp', 'collection' => 'weddings']);
    GalleryItem::factory()->create(['image' => 'gallery/b.webp', 'collection' => 'events']);

    $component = Livewire::test('pages::admin.testimonials')->call('openGalleryPicker');

    expect($component->get('galleryChoices'))->toHaveCount(3);

    $component->set('galleryCollection', 'weddings');

    expect($component->get('galleryChoices')->pluck('image')->all())->toBe(['gallery/a.webp']);
});
