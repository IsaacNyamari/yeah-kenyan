<?php

use App\Models\GalleryItem;
use App\Models\Page;
use App\Models\Post;
use Database\Seeders\PageSeeder;

/*
 * Two families of image path coexist:
 *   - legacy paths copied straight into public/ (uploads/…, images/…)
 *   - CMS uploads written to the public storage disk (posts/…, gallery/…, pages/…)
 * Routing one through the other's resolver yields a dead URL, so both are pinned here.
 */

it('serves legacy public paths straight from the web root', function (string $path) {
    $page = Page::factory()->create(['image' => $path]);

    expect($page->image_url)
        ->toBe(asset($path))
        ->not->toContain('/storage/');
})->with([
    'uploads/wedding-1.jpg',
    'images/eventplanning.jpg',
]);

it('serves cms uploads from the storage disk', function (string $path) {
    $page = Page::factory()->create(['image' => $path]);

    expect($page->image_url)->toContain('/storage/'.$path);
})->with([
    'pages/lighting-abc123.webp',
    'posts/story-def456.webp',
    'gallery/gala-ghi789.webp',
]);

it('resolves the same way for posts and gallery items', function () {
    $post = Post::factory()->create(['image' => 'uploads/event-images-15.jpeg']);
    $item = GalleryItem::factory()->create(['image' => 'gallery/new-upload.webp']);

    expect($post->image_url)->toBe(asset('uploads/event-images-15.jpeg'))
        ->and($item->image_url)->toContain('/storage/gallery/new-upload.webp');
});

it('returns null when no image is set', function () {
    expect(Page::factory()->create(['image' => null])->image_url)->toBeNull();
});

it('points every seeded service page at a file that exists on disk', function () {
    $this->seed(PageSeeder::class);

    Page::whereNotNull('image')->each(function (Page $page): void {
        expect(public_path($page->image))->toBeFile("Missing image for {$page->slug}");
    });
});
