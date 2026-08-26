<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncoderInterface;
use Throwable;

/**
 * Re-encodes the images already sitting in public/ at a lower quality.
 *
 * The set migrated from the legacy site came straight off cameras and phones,
 * so most files are far larger than the page ever needs. Each image is
 * re-encoded in place, keeping its original filename and format so every
 * database reference and hard-coded path stays valid.
 *
 * Files that would not actually get smaller are left untouched.
 */
class CompressImages extends Command
{
    protected $signature = 'images:compress
                            {--quality=80 : Encoder quality, 1-100}
                            {--dir=* : Directories under public/ to process (default: uploads, images)}
                            {--max-width=1920 : Scale anything wider down to this}
                            {--dry-run : Report the savings without writing files}';

    protected $description = 'Compress the images in public/ in place, preserving filenames and formats';

    public function __construct(private readonly ImageManager $manager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $quality = (int) $this->option('quality');

        if ($quality < 1 || $quality > 100) {
            $this->components->error('Quality must be between 1 and 100.');

            return self::FAILURE;
        }

        $directories = $this->option('dir') ?: ['uploads', 'images'];
        $maxWidth = (int) $this->option('max-width');
        $dryRun = (bool) $this->option('dry-run');

        $files = $this->gatherFiles($directories);

        if ($files === []) {
            $this->components->warn('No images found.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf(
            '%s %d image(s) at quality %d%s.',
            $dryRun ? 'Would compress' : 'Compressing',
            count($files),
            $quality,
            $dryRun ? ' (dry run)' : '',
        ));

        if (! $dryRun && ! $this->option('no-interaction') && ! $this->confirm('This overwrites the originals. Continue?', true)) {
            $this->components->warn('Aborted.');

            return self::SUCCESS;
        }

        $before = 0;
        $after = 0;
        $touched = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            $originalSize = filesize($file) ?: 0;
            $before += $originalSize;

            try {
                $encoded = $this->encode($file, $quality, $maxWidth);
            } catch (Throwable $e) {
                $this->newLine();
                $this->components->warn(basename($file).': '.$e->getMessage());
                $after += $originalSize;
                $skipped++;
                $bar->advance();

                continue;
            }

            // Never trade a smaller file for a bigger one.
            if ($encoded === null || strlen($encoded) >= $originalSize) {
                $after += $originalSize;
                $skipped++;
                $bar->advance();

                continue;
            }

            if (! $dryRun) {
                file_put_contents($file, $encoded);
            }

            $after += strlen($encoded);
            $touched++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $saved = $before - $after;

        $this->components->twoColumnDetail('Compressed', (string) $touched);
        $this->components->twoColumnDetail('Left alone (already small)', (string) $skipped);
        $this->components->twoColumnDetail('Before', $this->humanBytes($before));
        $this->components->twoColumnDetail('After', $this->humanBytes($after));
        $this->components->twoColumnDetail(
            'Saved',
            sprintf('%s (%d%%)', $this->humanBytes($saved), $before > 0 ? round($saved / $before * 100) : 0),
        );

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $directories
     * @return array<int, string>
     */
    private function gatherFiles(array $directories): array
    {
        $files = [];

        foreach ($directories as $directory) {
            $path = public_path(trim($directory, '/'));

            if (! is_dir($path)) {
                $this->components->warn("Not a directory: $path");

                continue;
            }

            foreach (glob($path.'/*') ?: [] as $file) {
                if (is_file($file) && $this->encoderFor($file) !== null) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    private function encode(string $file, int $quality, int $maxWidth): ?string
    {
        $encoder = $this->encoderFor($file, $quality);

        if ($encoder === null) {
            return null;
        }

        $image = $this->manager->decodePath($file);

        // scaleDown never enlarges, so smaller images keep their dimensions.
        $image->scaleDown(width: $maxWidth);

        return (string) $image->encode($encoder);
    }

    /**
     * Match the encoder to the file's existing format so the extension stays honest.
     */
    private function encoderFor(string $file, ?int $quality = null): ?EncoderInterface
    {
        return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => new JpegEncoder(quality: $quality ?? 80, progressive: true),
            'png' => new PngEncoder(interlaced: false),
            'webp' => new WebpEncoder(quality: $quality ?? 80, strip: true),
            default => null,
        };
    }

    private function humanBytes(int $bytes): string
    {
        return match (true) {
            $bytes >= 1_073_741_824 => round($bytes / 1_073_741_824, 2).' GB',
            $bytes >= 1_048_576 => round($bytes / 1_048_576, 1).' MB',
            default => round($bytes / 1024).' KB',
        };
    }
}
