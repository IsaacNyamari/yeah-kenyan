<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Pulls the latest code and rebuilds the caches, driven from the dashboard.
 *
 * Shared hosting is the reason this exists: there is no reliable shell, so the
 * alternative to a button is uploading files by FTP. Each step is a separate
 * call so the browser can run them one at a time and show what is happening,
 * rather than staring at one long request that may or may not still be alive.
 */
class GitDeployer
{
    /**
     * Long enough for a slow fetch over SSH, short enough that a hung command
     * surfaces as an error rather than a dead page.
     */
    private const TIMEOUT = 120;

    public function __construct(private AppVersion $version) {}

    /**
     * Whether this server can run the deploy at all.
     *
     * @return array{ok: bool, reason: string|null}
     */
    public function support(): array
    {
        if (! $this->canStartProcesses()) {
            return [
                'ok' => false,
                'reason' => 'PHP cannot start external processes on this server (proc_open is disabled), '
                    .'so git cannot be run from the dashboard. Deploy over FTP or ask the host to allow proc_open.',
            ];
        }

        if (! is_dir(base_path('.git'))) {
            return [
                'ok' => false,
                'reason' => 'This copy of the site is not a git repository, so there is nothing to pull. '
                    .'It was most likely uploaded as files rather than cloned.',
            ];
        }

        $git = $this->run(['git', '--version'], timeout: 10);

        if (! $git['ok']) {
            return [
                'ok' => false,
                'reason' => 'The git command is not available to the web server. '.$git['error'],
            ];
        }

        return ['ok' => true, 'reason' => null];
    }

    /**
     * How far behind the remote this copy is.
     *
     * @return array{ok: bool, branch: string, behind: int, upToDate: bool, commits: list<string>, current: string, installedVersion: string, latestVersion: string|null, error: string|null}
     */
    public function status(): array
    {
        $blank = [
            'ok' => false, 'branch' => '', 'behind' => 0, 'upToDate' => false,
            'commits' => [], 'current' => '',
            'installedVersion' => $this->version->current(), 'latestVersion' => null,
            'error' => null,
        ];

        $branch = $this->run(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], timeout: 15);

        if (! $branch['ok']) {
            return [...$blank, 'error' => $branch['error']];
        }

        $name = trim($branch['output']);

        // One network call for both questions: --tags brings the release tags
        // down with the branch, so latestRelease() can read them locally instead
        // of opening a second connection.
        $fetch = $this->run(['git', 'fetch', '--tags', 'origin', $name], timeout: 60);

        if (! $fetch['ok']) {
            return [...$blank, 'branch' => $name, 'error' => $fetch['error']];
        }

        $behind = $this->run(['git', 'rev-list', '--count', "HEAD..origin/{$name}"], timeout: 30);

        if (! $behind['ok']) {
            return [...$blank, 'branch' => $name, 'error' => $behind['error']];
        }

        $count = (int) trim($behind['output']);

        $log = $this->run(
            ['git', 'log', '--no-merges', '--pretty=format:%h  %s  (%an, %ar)', '-n', '15', "HEAD..origin/{$name}"],
            timeout: 30,
        );

        $current = $this->run(['git', 'log', '-1', '--pretty=format:%h  %s'], timeout: 15);

        return [
            'ok' => true,
            'branch' => $name,
            'behind' => $count,
            'upToDate' => $count === 0,
            'commits' => array_values(array_filter(explode("\n", trim($log['output'])))),
            'current' => trim($current['output']),
            'installedVersion' => $this->version->current(),
            'latestVersion' => $this->latestRelease(),
            'error' => null,
        ];
    }

    /**
     * @return array{ok: bool, output: string, error: string|null}
     */
    public function pull(string $branch = 'main'): array
    {
        /*
         * --ff-only on purpose. A deploy target should only ever move forward;
         * if it has somehow diverged, a plain pull would either create a merge
         * commit or leave conflict markers in live files. Failing here is a
         * problem someone can look at, rather than a broken site.
         */
        $result = $this->run(['git', 'pull', '--ff-only', 'origin', $branch]);

        if (! $result['ok']) {
            return $result;
        }

        return $result;
    }

    /**
     * @return array{ok: bool, output: string, error: string|null}
     */
    public function migrate(): array
    {
        try {
            Artisan::call('migrate', ['--force' => true]);

            return ['ok' => true, 'output' => trim(Artisan::output()), 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'output' => '', 'error' => $e->getMessage()];
        }
    }

    /**
     * Copy the freshly built assets into the directory the web server serves.
     *
     * On cPanel the repository lives outside the web root and public/ is served
     * from public_html, so a pull updates public/build while the browser keeps
     * loading whatever is in public_html/build. The page then renders new
     * markup against an old bundle — components that no longer exist, or, when
     * the previous bundle was empty, none at all.
     *
     * Only build/ is copied. index.php in the web root is rewritten during
     * cPanel setup to point back at this directory, and uploads and the storage
     * link live there too — overwriting any of them would break the site.
     *
     * @return array{ok: bool, output: string, error: string|null}
     */
    public function publishAssets(): array
    {
        $source = base_path('public/build');
        $target = rtrim(public_path('build'), DIRECTORY_SEPARATOR);

        if (! is_dir($source)) {
            return [
                'ok' => true,
                'output' => 'No build directory in the repository; nothing to publish.',
                'error' => null,
            ];
        }

        // The usual local layout: the web root is the repository's own public
        // directory, so the files are already where they need to be.
        if (realpath($source) === realpath($target)) {
            return [
                'ok' => true,
                'output' => 'Assets are served straight from the repository; nothing to copy.',
                'error' => null,
            ];
        }

        try {
            File::ensureDirectoryExists($target);

            if (! File::copyDirectory($source, $target)) {
                return [
                    'ok' => false,
                    'output' => '',
                    'error' => "Could not copy assets into {$target}. Check that it is writable by the web user.",
                ];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'output' => '', 'error' => $e->getMessage()];
        }

        return [
            'ok' => true,
            'output' => 'Published '.count(File::files($source))." file(s) to {$target}",
            'error' => null,
        ];
    }

    /**
     * Rebuild what makes the site fast, and nothing else.
     *
     * Two commands are deliberately absent.
     *
     * config:cache boots a fresh application to collect config, which runs
     * SettingsServiceProvider, which decrypts the mail password and the Google
     * service-account key out of the database — and the whole array is then
     * written to bootstrap/cache/config.php in plaintext. Clearing it instead
     * also removes any such file left behind by an earlier deploy.
     *
     * view:cache clears storage/framework/views first, which takes Livewire's
     * compiled single-file components with it. Livewire decides a component is
     * already compiled by looking only for its class file, and treats a
     * missing compiled view as present, so any state where the class survives
     * without the view renders a 500 that no amount of traffic clears. It also
     * cannot rebuild them: they are compiled by Livewire, not by Blade.
     *
     * @return array{ok: bool, output: string, error: string|null}
     */
    public function refreshCaches(): array
    {
        $lines = [];

        try {
            foreach (['config:clear', 'route:cache'] as $command) {
                Artisan::call($command);
                $lines[] = $command.': '.(trim(Artisan::output()) ?: 'done');
            }

            return ['ok' => true, 'output' => implode("\n", $lines), 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'output' => implode("\n", $lines), 'error' => $e->getMessage()];
        }
    }

    /**
     * The newest version tag published on the remote.
     *
     * Only vX.Y.Z tags count. A repository also carries tags that are names
     * rather than versions, and there is no sensible way to tell whether one of
     * those is newer than what is installed.
     */
    public function latestRelease(): ?string
    {
        // Local, so it costs no network round trip. status() fetches tags first,
        // which is what keeps this current.
        $result = $this->run(['git', 'tag', '--list'], timeout: 15);

        if (! $result['ok']) {
            return null;
        }

        $versions = [];

        foreach (explode("\n", $result['output']) as $line) {
            if (preg_match('/^(v?\d+\.\d+\.\d+)$/', trim($line), $match) === 1) {
                $versions[] = ltrim($match[1], 'vV');
            }
        }

        if ($versions === []) {
            return null;
        }

        // Closure rather than the function name: version_compare takes an
        // optional third argument that usort does not supply.
        usort($versions, fn (string $a, string $b): int => version_compare($a, $b));

        return end($versions);
    }

    /**
     * The configured origin URL, or an empty string when there is none.
     */
    public function remote(): string
    {
        return trim($this->run(['git', 'remote', 'get-url', 'origin'], timeout: 15)['output']);
    }

    /**
     * Whether this server can reach GitHub over ordinary HTTPS.
     *
     * Worth knowing on a network failure: if HTTPS works while SSH does not,
     * switching the remote is a fix rather than a guess.
     */
    public function httpsReachable(): bool
    {
        if (! function_exists('curl_init')) {
            return false;
        }

        $handle = curl_init('https://github.com');

        if ($handle === false) {
            return false;
        }

        curl_setopt_array($handle, [
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        curl_exec($handle);
        $failed = curl_errno($handle) !== 0;
        curl_close($handle);

        return ! $failed;
    }

    /**
     * Turn a raw git failure into the thing to do about it.
     *
     * Shared hosting produces a small set of failures over and over, and the
     * raw text for each says almost nothing about the cause. Without this the
     * dashboard shows an SSH error to someone whose actual problem is that the
     * host blocks outbound SSH.
     */
    public function diagnose(?string $error): ?string
    {
        if (blank($error)) {
            return null;
        }

        $usingSsh = str_starts_with($this->remote(), 'git@') || str_contains($this->remote(), 'ssh://');

        return match (true) {
            str_contains($error, 'Could not resolve hostname'),
            str_contains($error, 'Connection timed out'),
            str_contains($error, 'Network is unreachable') => match (true) {
                $usingSsh && $this->httpsReachable() => 'This server can reach github.com over HTTPS but not over '
                    .'SSH — checked just now. Shared hosting commonly blocks outbound port 22 and gives the web '
                    .'user a restricted resolver. Switch the remote to HTTPS with a read-only access token and '
                    .'this will work.',
                $usingSsh => 'The server cannot reach GitHub over SSH, and could not reach it over HTTPS either. '
                    .'Outbound connections look blocked for the web user, so deploying from the dashboard is not '
                    .'possible on this host.',
                default => 'The server cannot reach GitHub over HTTPS. Outbound connections may be blocked, or DNS '
                    .'is unavailable to the web user.',
            },

            str_contains($error, 'Permission denied (publickey)'),
            str_contains($error, 'Host key verification failed') => 'GitHub refused the SSH key. The key belongs to '
                .'your shell account, and the web server runs as a different user that cannot read it. An HTTPS '
                .'remote with a read-only access token avoids the problem entirely.',

            str_contains($error, 'Authentication failed'),
            str_contains($error, 'could not read Username') => 'GitHub rejected the credentials on the HTTPS remote. '
                .'The access token has probably expired or lacks read access to this repository.',

            str_contains($error, 'local changes'),
            str_contains($error, 'would be overwritten') => 'Files on the server differ from the repository, so the '
                .'pull was refused rather than overwriting them. Something was edited directly on the server.',

            str_contains($error, 'Not possible to fast-forward'),
            str_contains($error, 'diverging') => 'The copy on the server has commits that are not on the remote, so '
                .'it cannot fast-forward. It needs sorting out on the server before deploying again.',

            str_contains($error, 'proc_open') => 'This server does not let PHP start external processes, so git '
                .'cannot be run from the dashboard at all.',

            default => null,
        };
    }

    /**
     * @param  list<string>  $command
     * @return array{ok: bool, output: string, error: string|null}
     */
    private function run(array $command, int $timeout = self::TIMEOUT): array
    {
        if (! $this->canStartProcesses()) {
            return [
                'ok' => false,
                'output' => '',
                'error' => 'PHP cannot start external processes on this server (proc_open is disabled).',
            ];
        }

        try {
            $process = new Process($command, base_path(), $this->environment(), null, $timeout);
            $process->run();
        } catch (\Throwable $e) {
            return ['ok' => false, 'output' => '', 'error' => $e->getMessage()];
        }

        $output = trim($process->getOutput());
        $error = trim($process->getErrorOutput());

        return [
            'ok' => $process->isSuccessful(),
            'output' => $output,
            // git writes ordinary progress to stderr, so on success that text
            // is information rather than a fault.
            'error' => $process->isSuccessful() ? null : ($error ?: $output ?: 'The command failed with no output.'),
        ];
    }

    /**
     * Environment for the git process.
     *
     * The parent environment is copied in explicitly. Symfony only merges it
     * for you when PHP has populated $_ENV, and variables_order commonly omits
     * E — so passing an array of overrides alone hands the child a near-empty
     * environment with no PATH and no SystemRoot. git then fails before it
     * starts, with a name-resolution error that points nowhere near the cause.
     *
     * @return array<string, string>
     */
    private function environment(): array
    {
        // Never let git stop for a username or passphrase; there is no
        // terminal here, and a prompt would hang until the timeout.
        return [...getenv(), 'GIT_TERMINAL_PROMPT' => '0'];
    }

    private function canStartProcesses(): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('proc_open', $disabled, true);
    }
}
