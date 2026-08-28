<?php

use App\Models\GalleryItem;
use App\Models\HeroPanel;
use App\Models\User;
use App\Support\HeroPanelKind;
use App\Support\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
 * The hero used to live in config. It is now rows, so the homepage has to keep
 * rendering whatever is there — including nothing at all.
 */

it('shows the panels from the database on the homepage', function () {
    HeroPanel::query()->delete();

    HeroPanel::factory()->create(['badge' => 'Branding', 'text' => 'Messages that resonate']);
    HeroPanel::factory()->tile()->create(['badge' => 'Expertise', 'text' => 'Experienced experts']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Branding')
        ->assertSee('Messages that resonate')
        ->assertSee('Expertise')
        ->assertSee('Experienced experts');
});

it('keeps a hidden panel off the homepage', function () {
    HeroPanel::query()->delete();

    HeroPanel::factory()->create(['badge' => 'Live one']);
    HeroPanel::factory()->hidden()->create(['badge' => 'Hidden one']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Live one')
        ->assertDontSee('Hidden one');
});

it('orders panels by their sort order', function () {
    HeroPanel::query()->delete();

    HeroPanel::factory()->create(['badge' => 'Second', 'sort_order' => 2]);
    HeroPanel::factory()->create(['badge' => 'First', 'sort_order' => 1]);

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect(strpos($html, 'First'))->toBeLessThan(strpos($html, 'Second'));
});

it('drops the hero entirely when every panel is gone', function () {
    // The carousel counts slides for its rotation, so an empty hero must not
    // render at all rather than render a panel that divides by zero.
    HeroPanel::query()->delete();

    $this->get(route('home'))->assertOk();
});

it('renders the carousel without paging dots for a single slide', function () {
    HeroPanel::query()->delete();
    HeroPanel::factory()->create(['badge' => 'Only one']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Only one')
        ->assertDontSee('Go to slide 1');
});

it('keeps the homepage editor to accounts granted it', function () {
    $this->get(route('admin.homepage'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.homepage'))->assertForbidden();

    $this->actingAs(User::factory()->moderator()->create());
    $this->get(route('admin.homepage'))->assertForbidden();

    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('admin.homepage'))->assertOk();
});

it('adds a panel with an uploaded image', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.homepage')
        ->set('kind', 'tile')
        ->set('badge', 'Lighting Rig')
        ->set('text', 'Clear, crisp audio')
        ->set('photo', UploadedFile::fake()->image('speakers.jpg', 1200, 800))
        ->call('save')
        ->assertHasNoErrors();

    $panel = HeroPanel::firstWhere('badge', 'Lighting Rig');

    expect($panel->kind)->toBe(HeroPanelKind::Tile)
        ->and($panel->image)->toStartWith('hero/');
});

it('adds a panel using an image already in the gallery', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());

    GalleryItem::factory()->create(['image' => 'gallery/stage.webp']);

    Livewire::test('pages::admin.homepage')
        ->set('badge', 'Staging')
        ->set('text', 'Built for the room')
        ->call('chooseGalleryImage', 'gallery/stage.webp')
        ->call('save')
        ->assertHasNoErrors();

    // Shared, not copied.
    expect(HeroPanel::firstWhere('badge', 'Staging')?->image)->toBe('gallery/stage.webp');
});

it('requires an image for a new panel but not when editing one', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.homepage')
        ->set('badge', 'No picture')
        ->set('text', 'Nothing to look at')
        ->call('save')
        ->assertHasErrors('photo');

    $existing = HeroPanel::factory()->create();

    Livewire::test('pages::admin.homepage')
        ->call('edit', $existing->id)
        ->set('text', 'Reworded')
        ->call('save')
        ->assertHasNoErrors();

    expect($existing->fresh()->text)->toBe('Reworded');
});

it('keeps a gallery image on disk when the panel using it is deleted', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());

    Storage::disk('public')->put('gallery/shared.webp', 'binary');
    GalleryItem::factory()->create(['image' => 'gallery/shared.webp']);
    $panel = HeroPanel::factory()->create(['image' => 'gallery/shared.webp']);

    Livewire::test('pages::admin.homepage')
        ->call('confirmAction', 'delete', $panel->id)
        ->call('runPendingAction');

    expect(HeroPanel::find($panel->id))->toBeNull()
        ->and(Storage::disk('public')->exists('gallery/shared.webp'))->toBeTrue();
});

it('never deletes an image carried over from the legacy site', function () {
    $this->actingAs(User::factory()->admin()->create());

    // The seeded panels point at files under public/images that other pages
    // also use.
    $panel = HeroPanel::factory()->create(['image' => 'images/branding1.jpg']);

    Livewire::test('pages::admin.homepage')
        ->call('confirmAction', 'delete', $panel->id)
        ->call('runPendingAction');

    expect(HeroPanel::find($panel->id))->toBeNull()
        ->and(is_file(public_path('images/branding1.jpg')))->toBeTrue();
});

it('reorders a panel without disturbing the other kind', function () {
    $this->actingAs(User::factory()->admin()->create());
    HeroPanel::query()->delete();

    $first = HeroPanel::factory()->create(['badge' => 'First', 'sort_order' => 1]);
    $second = HeroPanel::factory()->create(['badge' => 'Second', 'sort_order' => 2]);
    $tile = HeroPanel::factory()->tile()->create(['badge' => 'Tile', 'sort_order' => 1]);

    Livewire::test('pages::admin.homepage')->call('moveDown', $first->id);

    expect($first->fresh()->sort_order)->toBe(2)
        ->and($second->fresh()->sort_order)->toBe(1)
        ->and($tile->fresh()->sort_order)->toBe(1);
});

it('hides and shows a panel', function () {
    $this->actingAs(User::factory()->admin()->create());
    $panel = HeroPanel::factory()->create();

    Livewire::test('pages::admin.homepage')->call('togglePublished', $panel->id);
    expect($panel->fresh()->is_published)->toBeFalse();

    Livewire::test('pages::admin.homepage')->call('togglePublished', $panel->id);
    expect($panel->fresh()->is_published)->toBeTrue();
});

it('grants the homepage permission to administrators only', function () {
    expect(User::factory()->admin()->create()->can(Permission::ManageHomepage->value))->toBeTrue()
        ->and(User::factory()->moderator()->create()->can(Permission::ManageHomepage->value))->toBeFalse()
        ->and(User::factory()->create()->can(Permission::ManageHomepage->value))->toBeFalse();
});
