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
