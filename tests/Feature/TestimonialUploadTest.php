<?php

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('creates a testimonial with an optimized portrait', function () {
    Storage::fake('public');

    Livewire::test('pages::admin.testimonials')
        ->set('client', 'Church Events')
        ->set('role', 'Event Production')
        ->set('quote', 'They handled the whole production end to end.')
        ->set('photo', UploadedFile::fake()->image('portrait.jpg', 1800, 1800))
        ->call('save')
        ->assertHasNoErrors();

    $testimonial = Testimonial::firstWhere('client', 'Church Events');

    expect($testimonial)->not->toBeNull()
        ->and($testimonial->image)->toStartWith('testimonials/')->toEndWith('.webp');

    Storage::disk('public')->assertExists($testimonial->image);
});

it('creates a testimonial without a photo', function () {
    Livewire::test('pages::admin.testimonials')
        ->set('client', 'Real Estate')
        ->set('quote', 'Great value for money on the whole shoot.')
        ->call('save')
        ->assertHasNoErrors();

    expect(Testimonial::firstWhere('client', 'Real Estate'))->not->toBeNull();
});

/*
 * A failure inside GD used to surface as a 500 with nothing shown to the
 * author. Decoding is memory-bound on pixel count rather than file size, so a
 * phone photo can exhaust a modest shared-hosting memory_limit.
 */
it('reports an unprocessable image as a field error instead of failing the request', function () {
    // Small enough limit that any real image is refused by the guard.
    $original = ini_get('memory_limit');
    ini_set('memory_limit', '8M');

    try {
        Livewire::test('pages::admin.testimonials')
            ->set('client', 'Oversized')
            ->set('quote', 'This upload should be refused politely.')
            ->set('photo', UploadedFile::fake()->image('huge.jpg', 4000, 3000))
            ->call('save')
            ->assertHasErrors('photo')
            ->assertOk();
    } finally {
        ini_set('memory_limit', $original);
    }

    expect(Testimonial::firstWhere('client', 'Oversized'))->toBeNull();
});

it('rejects a file that is not a readable image', function () {
    Livewire::test('pages::admin.testimonials')
        ->set('client', 'Not An Image')
        ->set('quote', 'This should not be accepted at all.')
        ->set('photo', UploadedFile::fake()->create('notes.pdf', 40))
        ->call('save')
        ->assertHasErrors('photo');

    expect(Testimonial::firstWhere('client', 'Not An Image'))->toBeNull();
});
