<?php

use App\Models\User;
use App\Services\AppVersion;
use App\Services\GitDeployer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/*
 * The deploy pulls code onto the server and runs migrations, so most of what
 * matters here is who may trigger it and what happens when a step fails.
 *
 * It runs over ordinary JSON endpoints rather than Livewire: the code changes
 * underneath the open page, and a Livewire snapshot produced by the previous
 * version cannot hydrate against the new one. These tests drive those
 * endpoints. A fake deployer stands in for git — nothing here may touch the
 * network or the working tree.
 */

/**
 * @param  array<string, mixed>  $overrides
 */
function fakeDeployer(array $overrides = []): GitDeployer
{
    $fake = new class(new AppVersion) extends GitDeployer
    {
        /** @var array<string, mixed> */
        public array $behaviour = [];

        /** @var list<string> */
        public array $called = [];

        public function support(): array
        {
            return $this->behaviour['support'] ?? ['ok' => true, 'reason' => null];
        }

        public function status(): array
        {
            return $this->behaviour['status'] ?? [
                'ok' => true, 'branch' => 'main', 'behind' => 0, 'upToDate' => true,
                'commits' => [], 'current' => 'abc1234 latest',
                'installedVersion' => '1.0.0', 'latestVersion' => '1.0.0', 'error' => null,
            ];
        }

        public function remote(): string
        {
            return 'git@github.com:example/example.git';
        }

        public function pull(string $branch = 'main'): array
        {
            $this->called[] = 'pull:'.$branch;

            return $this->behaviour['pull'] ?? ['ok' => true, 'output' => 'Updated 3 files', 'error' => null];
        }

        public function migrate(): array
        {
            $this->called[] = 'migrate';

            return $this->behaviour['migrate'] ?? ['ok' => true, 'output' => 'Nothing to migrate.', 'error' => null];
        }

        public function publishAssets(): array
        {
            $this->called[] = 'assets';

            return $this->behaviour['assets'] ?? ['ok' => true, 'output' => 'Published 4 file(s)', 'error' => null];
        }

        public function refreshCaches(): array
        {
            $this->called[] = 'cache';

            return $this->behaviour['cache'] ?? ['ok' => true, 'output' => 'done', 'error' => null];
        }
    };

    $fake->behaviour = $overrides;

    app()->instance(GitDeployer::class, $fake);

    return $fake;
}

/**
 * @return array<string, mixed>
 */
function behindStatus(int $behind = 2, ?string $latestVersion = null): array
{
    return [
        'ok' => true, 'branch' => 'main', 'behind' => $behind, 'upToDate' => false,
        'commits' => ['abc1234  Add a thing  (Isaac, 1 hour ago)'],
        'current' => 'def5678  the previous one',
        'installedVersion' => '1.0.0', 'latestVersion' => $latestVersion, 'error' => null,
    ];
}

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('keeps the deploy endpoints to accounts that manage settings', function () {
    foreach ([User::factory()->create(), User::factory()->moderator()->create()] as $account) {
        $this->actingAs($account);

        $this->postJson(route('admin.deploy.check'))->assertForbidden();
        $this->postJson(route('admin.deploy.step', ['step' => 'pull']))->assertForbidden();
    }
});

it('refuses the deploy endpoints to a guest', function () {
    auth()->logout();

    $this->postJson(route('admin.deploy.check'))->assertUnauthorized();
});

it('says so when the site is already up to date', function () {
    fakeDeployer();

    $this->postJson(route('admin.deploy.check'))
        ->assertOk()
        ->assertJson(['ok' => true, 'upToDate' => true, 'blocked' => null]);
});

it('lists the commits waiting to be deployed', function () {
    fakeDeployer(['status' => behindStatus(2)]);

    $this->postJson(route('admin.deploy.check'))
        ->assertOk()
        ->assertJson(['behind' => 2, 'upToDate' => false])
        ->assertJsonFragment(['abc1234  Add a thing  (Isaac, 1 hour ago)']);
});

it('reports a server that cannot run git rather than failing silently', function () {
    fakeDeployer(['support' => ['ok' => false, 'reason' => 'proc_open is disabled.']]);

    $this->postJson(route('admin.deploy.check'))
        ->assertOk()
        ->assertJson(['ok' => false, 'blocked' => 'proc_open is disabled.']);
});

it('flags an update when the published release is newer', function () {
    fakeDeployer(['status' => behindStatus(1, '99.0.0')]);

    $this->postJson(route('admin.deploy.check'))
        ->assertOk()
        ->assertJson(['releaseAvailable' => true, 'latestVersion' => '99.0.0']);
});

it('does not flag an update for an older or unversioned tag', function () {
    fakeDeployer(['status' => behindStatus(1, '0.0.1')]);
    $this->postJson(route('admin.deploy.check'))->assertJson(['releaseAvailable' => false]);

    // The repository carries a tag named after the project rather than a
    // version, which cannot be ordered against anything.
    fakeDeployer(['status' => behindStatus(1, 'yeah_keanyan')]);
    $this->postJson(route('admin.deploy.check'))->assertJson(['releaseAvailable' => false]);
});

it('runs each step on request', function () {
    $fake = fakeDeployer();

    foreach (['pull', 'migrate', 'assets', 'cache'] as $step) {
        $this->postJson(route('admin.deploy.step', ['step' => $step]), ['branch' => 'main'])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    expect($fake->called)->toBe(['pull:main', 'migrate', 'assets', 'cache']);
});

it('reports a failed step with the reason and an explanation', function () {
    fakeDeployer([
        'pull' => ['ok' => false, 'output' => '', 'error' => 'error: Your local changes would be overwritten'],
    ]);

    $this->postJson(route('admin.deploy.step', ['step' => 'pull']))
        ->assertOk()
        ->assertJson(['ok' => false])
        ->assertJsonPath('error', 'error: Your local changes would be overwritten')
        ->assertJsonPath('hint', fn (?string $hint): bool => str_contains((string) $hint, 'edited directly on the server'));
});

it('refuses a step it does not recognise', function () {
    fakeDeployer();

    $this->postJson('/admin/deploy/step/rm-rf')->assertNotFound();
});

it('will not hand an arbitrary branch name to git', function () {
    // The branch arrives from the browser, so it is checked rather than passed
    // straight through as a command argument.
    $fake = fakeDeployer();

    $this->postJson(route('admin.deploy.step', ['step' => 'pull']), ['branch' => 'main; rm -rf /'])
        ->assertOk();

    expect($fake->called)->toBe(['pull:main']);
});

it('leaves the assets alone when the web root is the repository', function () {
    // The local layout: public/ is the web root, so there is nowhere to copy to
    // and copying onto itself would be the only way to get it wrong.
    $result = app(GitDeployer::class)->publishAssets();

    expect($result['ok'])->toBeTrue()
        ->and($result['output'])->toContain('nothing to copy');
});

it('clears the cached config instead of building one', function () {
    // config:cache boots a fresh application, which decrypts the mail password
    // and the Google service-account key into config — and then writes the lot
    // to this file in plaintext. Deploying must remove it, not create it.
    $cached = base_path('bootstrap/cache/config.php');
    File::put($cached, '<?php return [];');

    try {
        app(GitDeployer::class)->refreshCaches();

        expect(File::exists($cached))->toBeFalse();
    } finally {
        // This runs the real artisan commands, which cache routes in the
        // working copy. Left behind, they serve stale routes to whoever runs
        // the suite next.
        Artisan::call('route:clear');
    }
});

it('leaves livewire compiled components alone', function () {
    /*
     * view:cache clears storage/framework/views, which takes Livewire's
     * compiled components with it. Livewire then sees the class file and
     * concludes the component is compiled, while the view it points at is
     * gone — a 500 on every page that no amount of traffic clears.
     */
    $dir = storage_path('framework/views/livewire/views');
    File::ensureDirectoryExists($dir);
    File::put($dir.'/deploy-probe.blade.php', 'compiled');

    try {
        app(GitDeployer::class)->refreshCaches();

        expect(File::exists($dir.'/deploy-probe.blade.php'))->toBeTrue();
    } finally {
        File::delete($dir.'/deploy-probe.blade.php');
        Artisan::call('route:clear');
    }
});

it('hands git the full parent environment', function () {
    /*
     * Symfony only merges the parent environment when PHP has populated $_ENV,
     * and variables_order commonly omits E. Passing overrides alone left git
     * with no PATH and no SystemRoot, which surfaced as a name-resolution
     * failure pointing nowhere near the cause.
     */
    $support = app(GitDeployer::class)->support();

    expect($support['ok'])->toBeTrue()
        ->and($support['reason'])->toBeNull();
})->skip(
    fn (): bool => ! function_exists('proc_open') || ! is_dir(base_path('.git')),
    'Needs a git checkout and proc_open.',
);

it('explains an ssh failure as a hosting limitation, not a mystery', function () {
    $hint = app(GitDeployer::class)->diagnose(
        'ssh: Could not resolve hostname github.com: Non-recoverable failure in name resolution',
    );

    expect($hint)->toBeString()->toContain('HTTPS');
});

it('explains a refused key and a rejected token differently', function () {
    $deployer = app(GitDeployer::class);

    expect($deployer->diagnose('git@github.com: Permission denied (publickey).'))
        ->toContain('SSH key')
        ->and($deployer->diagnose('fatal: Authentication failed for https://github.com/x/y.git'))
        ->toContain('access token');
});

it('offers no explanation for a failure it does not recognise', function () {
    // Better a raw message than a confidently wrong one.
    expect(app(GitDeployer::class)->diagnose('something nobody has seen before'))->toBeNull()
        ->and(app(GitDeployer::class)->diagnose(null))->toBeNull();
});
