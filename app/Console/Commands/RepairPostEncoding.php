<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Repairs mojibake carried over from the legacy database.
 *
 * The old site stored UTF-8 bytes in a latin1 context, so curly quotes and
 * accented characters arrived here as sequences like "a€œ" and "A©". Encoding
 * the text back to Windows-1252 recovers the original bytes, which are then
 * valid UTF-8 again.
 *
 * Only strings that actually look broken are touched, and a repair is kept
 * only when it round-trips to valid UTF-8.
 */
class RepairPostEncoding extends Command
{
    protected $signature = 'news:repair-encoding {--dry-run : Report what would change without saving}';

    protected $description = 'Fix mis-encoded characters in imported post content';

    /**
     * Byte sequences that only appear when UTF-8 has been read as Windows-1252.
     */
    private const MOJIBAKE = '/\x{00E2}\x{20AC}|\x{00C3}[\x{0080}-\x{00BF}]|\x{00C2}[\x{00A0}-\x{00BF}]/u';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $repaired = 0;

        foreach (Post::all() as $post) {
            $changes = [];

            foreach (['title', 'excerpt', 'body'] as $field) {
                $original = (string) $post->{$field};
                $fixed = $this->repair($original);

                if ($fixed !== $original) {
                    $changes[$field] = $fixed;
                }
            }

            if ($changes === []) {
                continue;
            }

            $this->components->twoColumnDetail(
                Str::limit($post->title, 50),
                '<fg=green>'.implode(', ', array_keys($changes)).'</>',
            );

            if (! $dryRun) {
                $post->forceFill($changes)->save();
            }

            $repaired++;
        }

        $this->newLine();
        $this->components->info(sprintf(
            '%d post(s) %s.',
            $repaired,
            $dryRun ? 'would be repaired' : 'repaired',
        ));

        return self::SUCCESS;
    }

    /**
     * Undo one round of UTF-8-read-as-Windows-1252, repeatedly if it was
     * double-encoded, but only while the result stays valid UTF-8.
     */
    private function repair(string $text): string
    {
        $passes = 0;

        while (preg_match(self::MOJIBAKE, $text) && $passes < 3) {
            $candidate = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

            // A failed conversion, or one that produces invalid UTF-8, means
            // the text was not mojibake after all — leave it alone.
            if ($candidate === false || $candidate === '' || ! mb_check_encoding($candidate, 'UTF-8')) {
                break;
            }

            $text = $candidate;
            $passes++;
        }

        return $text;
    }
}
