<?php

use App\Models\GalleryItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Database\Seeders\PageSeeder;

beforeEach(function () {
    // Services, classes and contact settings live in the database now.
    $this->seed(PageSeeder::class);
});

it('renders the homepage with seeded content', function () {
    $member = TeamMember::factory()->create(['name' => 'Francis Cleanheart']);
    $testimonial = Testimonial::factory()->create(['client' => 'Church Events']);
    $featured = Post::factory()->featured()->create(['title' => 'Crossover Night Coverage']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($member->name)
        ->assertSee($testimonial->client)
        ->assertSee($featured->title);
});

it('renders the about page', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Creating Unforgettable Experiences Since 2013')
        ->assertSee('Yes it is possible!');
});

it('renders the contact page with the office details', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Utawala, Nairobi, Kenya')
        ->assertSee('info@yeahkenyan.com');
});

it('renders the gallery with seeded images', function () {
    GalleryItem::factory()->create(['title' => 'Nairobi Gala']);

    $this->get(route('gallery'))->assertOk()->assertSee('Gallery');
});

it('renders every service and class page', function () {
    Page::published()->each(function (Page $page): void {
        $this->get(route('page', $page->slug))
            ->assertOk()
            ->assertSee($page->title);
    });
});

it('seeds all fifteen legacy service and class pages', function () {
    expect(Page::services()->count())->toBe(11)
        ->and(Page::classes()->count())->toBe(4);
});

it('hides unpublished pages', function () {
    $page = Page::factory()->unpublished()->create();

    $this->get(route('page', $page->slug))->assertNotFound();
});

it('returns 404 for a slug that is not a page', function () {
    $this->get('/not-a-real-service')->assertNotFound();
});

it('keeps the legacy service slugs so old links still resolve', function () {
    expect(Page::pluck('slug')->all())
        ->toContain('event-planning')
        ->toContain('videography-and-photography')
        ->toContain('ups-services-installation')
        ->toContain('high-quality-and-line-array-sound-system')
        ->toContain('science-and-physics-classes');
});
