<?php

namespace App\Console\Commands;

use App\Services\AppVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Cut a release: bump the VERSION file and tag the commit that carries it.
 *
 * Both in one command on purpose. A tag without the matching file leaves the
 * running site reporting the old version, and a file without the tag leaves
 * nothing for the deploy screen to compare against.
 */
class ReleaseCommand extends Command
{
    protected $signature = 'app:release
                            {version? : The new version, or major|minor|patch}
                            {--no-tag : Write the VERSION file without tagging}';

    protected $description = 'Bump the version and tag the release';

    public function handle(AppVersion $version): int
    {
        $current = $version->current();
        $this->line("Currently at <info>{$current}</info>");

        $next = $this->resolveVersion($version, $this->argument('version'));

        if ($next === null) {
            return self::FAILURE;
        }

        if (! $version->isBehind($next)) {
            $this->error("{$next} is not newer than {$current}.");

            return self::FAILURE;
        }

        File::put(base_path('VERSION'), $next."\n");
        $this->info("VERSION is now {$next}");

        if ($this->option('no-tag')) {
            $this->warn('Not tagged. Commit the VERSION file and tag it yourself, or the two will disagree.');

            return self::SUCCESS;
        }

        return $this->tag($next);
    }

    /**
     * Turn an argument into a concrete version, asking when none was given.
     */
    private function resolveVersion(AppVersion $version, ?string $argument): ?string
    {
        $steps = $version->nextVersions();

        if ($argument === null) {
            $choice = $this->choice(
                'What kind of change is this?',
                [
                    'patch' => "patch — {$steps['patch']} (fixes)",
                    'minor' => "minor — {$steps['minor']} (new features)",
                    'major' => "major — {$steps['major']} (breaking changes)",
                ],
                'patch',
            );

            // choice() answers with an array when asked for several; this asks
            // for one, so either shape resolves to a single key.
            $key = is_array($choice) ? (string) reset($choice) : $choice;

            return $steps[$key] ?? $steps['patch'];
        }

        if (isset($steps[$argument])) {
            return $steps[$argument];
        }

        if (! $version->isValid($argument)) {
            $this->error("\"{$argument}\" is not a version. Use major, minor, patch, or a number like 1.2.0.");

            return null;
        }

        return $version->normalise($argument);
    }

    private function tag(string $next): int
    {
        $tag = 'v'.$next;

        $commit = $this->git(['git', 'commit', '-m', "Release {$tag}", '--', 'VERSION']);

        if (! $commit['ok']) {
            $this->error('Could not commit the VERSION file: '.$commit['error']);

            return self::FAILURE;
        }

        $tagged = $this->git(['git', 'tag', '-a', $tag, '-m', "Release {$tag}"]);

        if (! $tagged['ok']) {
            $this->error("Could not create tag {$tag}: ".$tagged['error']);

            return self::FAILURE;
        }

        $this->info("Tagged {$tag}");
        $this->line('Publish it with: git push origin main --follow-tags');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $command
     * @return array{ok: bool, error: string}
     */
    private function git(array $command): array
    {
        $process = new Process($command, base_path(), [...getenv(), 'GIT_TERMINAL_PROMPT' => '0'], null, 60);
        $process->run();

        return [
            'ok' => $process->isSuccessful(),
            'error' => trim($process->getErrorOutput()) ?: trim($process->getOutput()),
        ];
    }
}
