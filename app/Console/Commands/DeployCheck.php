<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Pre-flight check for shared cPanel hosting.
 *
 * Verifies the things that actually break on shared hosting: writable paths,
 * a reachable database, uploads landing inside the web root, and the built
 * front-end assets being present.
 */
class DeployCheck extends Command
{
    protected $signature = 'deploy:check';

    protected $description = 'Verify the application is correctly set up for shared hosting';

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Deployment readiness');
        $this->newLine();

        $failures = 0;

        foreach ($this->checks() as $label => $check) {
            [$passed, $detail] = $check();

            $this->components->twoColumnDetail(
                $label,
                $passed ? '<fg=green>OK</> '.$detail : '<fg=red>FAIL</> '.$detail,
            );

            $failures += $passed ? 0 : 1;
        }

        $this->newLine();

        if ($failures > 0) {
            $this->components->error("$failures check(s) need attention before this will work.");

            return self::FAILURE;
        }

        $this->components->info('All checks passed.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, callable(): array{0: bool, 1: string}>
     */
    private function checks(): array
    {
        return [
            'PHP version' => fn (): array => [
                version_compare(PHP_VERSION, '8.3', '>='),
                PHP_VERSION,
            ],

            'Required extensions' => function (): array {
                $missing = array_values(array_filter(
                    ['mbstring', 'openssl', 'pdo', 'xml', 'curl', 'gd', 'fileinfo'],
                    fn (string $ext): bool => ! extension_loaded($ext),
                ));

                return [$missing === [], $missing === [] ? 'all present' : 'missing: '.implode(', ', $missing)];
            },

            'APP_KEY set' => fn (): array => [
                filled(config('app.key')),
                filled(config('app.key')) ? 'set' : 'run php artisan key:generate',
            ],

            'Debug disabled' => fn (): array => [
                ! config('app.debug') || ! app()->isProduction(),
                config('app.debug') ? 'APP_DEBUG=true' : 'off',
            ],

            'storage/ writable' => fn (): array => [
                is_writable(storage_path()),
                storage_path(),
            ],

            'bootstrap/cache writable' => fn (): array => [
                is_writable(base_path('bootstrap/cache')),
                base_path('bootstrap/cache'),
            ],

            'Database reachable' => function (): array {
                try {
                    DB::connection()->getPdo();

                    return [true, DB::connection()->getDatabaseName()];
                } catch (Throwable $e) {
                    return [false, str($e->getMessage())->limit(60)->toString()];
                }
            },

            'Migrations applied' => function (): array {
                try {
                    $pending = ! Setting::query()->exists() && DB::table('migrations')->count() === 0;

                    return [! $pending, $pending ? 'run php artisan migrate --force' : 'up to date'];
                } catch (Throwable) {
                    return [false, 'could not read migrations table'];
                }
            },

            'Uploads land in web root' => function (): array {
                $root = rtrim((string) config('filesystems.disks.public.root'), '/\\');
                $inside = str_starts_with($root, rtrim(public_path(), '/\\'));

                return [$inside, $inside ? $root : "outside public path: $root"];
            },

            'Upload directory writable' => function (): array {
                try {
                    Storage::disk('public')->put('.deploy-check', 'ok');
                    $ok = Storage::disk('public')->exists('.deploy-check');
                    Storage::disk('public')->delete('.deploy-check');

                    return [$ok, $ok ? 'writable' : 'write failed'];
                } catch (Throwable $e) {
                    return [false, str($e->getMessage())->limit(60)->toString()];
                }
            },

            'Front-end assets built' => fn (): array => [
                is_file(public_path('build/manifest.json')),
                is_file(public_path('build/manifest.json')) ? 'manifest present' : 'run npm run build',
            ],

            'Public path' => fn (): array => [true, public_path()],
        ];
    }
}
