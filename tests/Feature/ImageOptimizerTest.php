<?php

use App\Services\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->optimizer = app(ImageOptimizer::class);
});

it('converts an upload to webp on the public disk', function () {
    $path = $this->optimizer->store(
        UploadedFile::fake()->image('Wedding Photo.jpg', 800, 600),
        'posts',
    );

    expect($path)->toStartWith('posts/')
        ->and($path)->toEndWith('.webp')
        ->and($path)->toContain('wedding-photo');

    Storage::disk('public')->assertExists($path);
});

it('scales oversized images down to the max width', function () {
    $path = $this->optimizer->store(
        UploadedFile::fake()->image('huge.jpg', 4000, 2000),
        'posts',
        maxWidth: 1600,
    );

    $stored = imagecreatefromstring(Storage::disk('public')->get($path));

    expect(imagesx($stored))->toBe(1600);
});

it('leaves images smaller than the max width at their native size', function () {
    $path = $this->optimizer->store(
        UploadedFile::fake()->image('small.jpg', 600, 400),
        'posts',
        maxWidth: 1600,
    );

    $stored = imagecreatefromstring(Storage::disk('public')->get($path));

    expect(imagesx($stored))->toBe(600);
});

it('gives each upload a unique name so identical filenames do not collide', function () {
    $first = $this->optimizer->store(UploadedFile::fake()->image('photo.jpg'), 'gallery');
    $second = $this->optimizer->store(UploadedFile::fake()->image('photo.jpg'), 'gallery');

    expect($first)->not->toBe($second);

    Storage::disk('public')->assertExists($first);
    Storage::disk('public')->assertExists($second);
});

it('writes a square thumbnail alongside the image', function () {
    $result = $this->optimizer->storeWithThumbnail(
        UploadedFile::fake()->image('gala.jpg', 1200, 800),
        'gallery',
        thumbnailSize: 400,
    );

    Storage::disk('public')->assertExists($result['path']);
    Storage::disk('public')->assertExists($result['thumbnail']);

    $thumbnail = imagecreatefromstring(Storage::disk('public')->get($result['thumbnail']));

    expect(imagesx($thumbnail))->toBe(400)
        ->and(imagesy($thumbnail))->toBe(400);
});

it('deletes an image together with its thumbnail', function () {
    $result = $this->optimizer->storeWithThumbnail(
        UploadedFile::fake()->image('gala.jpg'),
        'gallery',
    );

    $this->optimizer->delete($result['path']);

    Storage::disk('public')->assertMissing($result['path']);
    Storage::disk('public')->assertMissing($result['thumbnail']);
});

it('ignores a delete for a blank path', function () {
    $this->optimizer->delete(null);
})->throwsNoExceptions();
