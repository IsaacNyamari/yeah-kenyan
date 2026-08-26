<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Normalises every uploaded image before it reaches disk.
 *
 * Uploads arrive straight from phones and DSLRs, so they are routinely
 * 5–10 MB. Each one is scaled down to a sane bound, stripped of metadata by
 * the re-encode, and written as WebP alongside a square thumbnail.
 */
class ImageOptimizer
{
    public function __construct(private readonly ImageManager $manager) {}

    /**
     * Store an optimized WebP copy of the upload and return its disk path.
     *
     * @param  string  $directory  Directory on the public disk, e.g. "posts"
     * @param  int  $maxWidth  Longest edge the stored image may have
     */
    public function store(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 1600,
        int $quality = 82,
    ): string {
        $image = $this->manager->decodePath($file->getRealPath());

        // scaleDown never enlarges, so smaller uploads keep their native size.
        $image->scaleDown(width: $maxWidth);

        $path = $directory.'/'.$this->filename($file);

        $this->disk()->put($path, (string) $image->encode(new WebpEncoder(quality: $quality, strip: true)));

        return $path;
    }

    /**
     * Store an optimized image together with a square thumbnail.
     *
     * @return array{path: string, thumbnail: string}
     */
    public function storeWithThumbnail(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 1600,
        int $thumbnailSize = 400,
    ): array {
        $path = $this->store($file, $directory, $maxWidth);

        $thumbnail = $this->manager->decodePath($file->getRealPath())
            ->cover($thumbnailSize, $thumbnailSize);

        $thumbnailPath = $directory.'/thumbnails/'.basename($path);

        $this->disk()->put($thumbnailPath, (string) $thumbnail->encode(new WebpEncoder(quality: 75, strip: true)));

        return ['path' => $path, 'thumbnail' => $thumbnailPath];
    }

    /**
     * Remove an image and any thumbnail generated alongside it.
     */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $this->disk()->delete([
            $path,
            dirname($path).'/thumbnails/'.basename($path),
        ]);
    }

    /**
     * Build a collision-free, URL-safe filename that keeps the original name readable.
     */
    private function filename(UploadedFile $file): string
    {
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        return Str::limit($name ?: 'image', 60, '').'-'.Str::random(8).'.webp';
    }

    private function disk(): Filesystem
    {
        return Storage::disk('public');
    }
}
