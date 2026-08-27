<?php

namespace App\Services;

use App\Exceptions\ImageProcessingException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Throwable;

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
        $source = $file->getRealPath();

        $this->guardAgainstOversizedImage($source);

        try {
            $image = $this->manager->decodePath($source);

            // scaleDown never enlarges, so smaller uploads keep their native size.
            $image->scaleDown(width: $maxWidth);

            $encoded = (string) $image->encode(new WebpEncoder(quality: $quality, strip: true));
        } catch (Throwable $e) {
            report($e);

            throw new ImageProcessingException(
                'That image could not be processed. Try a smaller file, or save it as a standard JPEG or PNG.',
                previous: $e,
            );
        }

        $path = $directory.'/'.$this->filename($file);

        if (! $this->disk()->put($path, $encoded)) {
            throw new ImageProcessingException('The image could not be saved. Check that the upload directory is writable.');
        }

        return $path;
    }

    /**
     * Refuse an image that cannot be decoded within the memory available.
     *
     * GD expands a JPEG into an uncompressed bitmap, needing roughly four
     * bytes per pixel regardless of how small the file is on disk. A 12
     * megapixel phone photo is only a few MB compressed but around 48 MB
     * decoded, which exhausts the modest memory_limit typical of shared
     * hosting — and a fatal there kills the whole request rather than raising
     * something catchable.
     */
    private function guardAgainstOversizedImage(string $source): void
    {
        $dimensions = @getimagesize($source);

        if ($dimensions === false) {
            throw new ImageProcessingException('That file does not appear to be a readable image.');
        }

        $limit = $this->memoryLimitInBytes();

        if ($limit === null) {
            return;
        }

        // Four bytes per pixel, plus headroom for the resized copy.
        $required = (int) ($dimensions[0] * $dimensions[1] * 4 * 1.8);
        $available = $limit - memory_get_usage(true);

        if ($required > $available) {
            throw new ImageProcessingException(sprintf(
                'That image is %d×%d, which is too large for this server to process. Resize it to around 2000px wide and try again.',
                $dimensions[0],
                $dimensions[1],
            ));
        }
    }

    /**
     * The memory limit in bytes, or null when unlimited.
     */
    private function memoryLimitInBytes(): ?int
    {
        $limit = trim((string) ini_get('memory_limit'));

        if ($limit === '' || $limit === '-1') {
            return null;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
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
