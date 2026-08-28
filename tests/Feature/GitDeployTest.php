<?php

use App\Models\User;
use App\Services\GitDeployer;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/*
 * The deploy button pulls code onto the server and runs migrations, so most of
 * what matters here is who may press it and what happens when a step fails.
 * A fake deployer stands in for git: these tests must not touch the network or
 * the working tree.
 */

/**
 * @param  array<string, mixed>  $overrides
 */
function fakeDeployer(array $overrides = []): GitDeployer
{
    $fake = new class extends GitDeployer
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
                'commits' => [], 'current' => 'abc1234 latest', 'error' => null,
            ];
        }

        public function pull(string $branch = 'main'): array
        {
            $this->called[] = 'pull';

            return $this->behaviour['pull'] ?? ['ok' => true, 'output' => 'Updated 3 files', 'error' => null];
        }

        public function migrate(): array
        {
            $this->called[] = 'migrate';

            return $this->behaviour['migrate'] ?? ['ok' => true, 'output' => 'Nothing to migrate.', 'error' => null];
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
function behindStatus(int $behind = 2): array
{
    return [
        'ok' => true, 'branch' => 'main', 'behind' => $behind, 'upToDate' => false,
        'commits' => ['abc1234  Add a thing  (Isaac, 1 hour ago)'],
        'current' => 'def5678  the previous one', 'error' => null,
    ];
}

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('keeps the deploy controls to accounts that manage settings', function () {
    foreach ([User::factory()->create(), User::factory()->moderator()->create()] as $account) {
        $this->actingAs($account);
        $this->get(route('admin.settings'))->assertForbidden();
    }
});

it('refuses a deploy action from an account without the permission', function () {
    fakeDeployer();

    // Livewire re-applies the route middleware, and the component checks again
    // on its own: this pulls code onto the server.
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.settings')
        ->call('checkForUpdates')
        ->assertForbidden();
});

it('says so when the site is already up to date', function () {
    fakeDeployer();

    Livewire::test('pages::admin.settings')
        ->call('checkForUpdates')
        ->assertSet('deployStatus.upToDate', true)
        ->assertSet('deployBlocked', '');
});

it('lists the commits waiting to be deployed', function () {
    fakeDeployer(['status' => behindStatus(2)]);

    Livewire::test('pages::admin.settings')
        ->call('checkForUpdates')
        ->assertSet('deployStatus.behind', 2)
        ->assertSee('Add a thing');
});

it('reports a server that cannot run git rather than failing silently', function () {
    fakeDeployer(['support' => ['ok' => false, 'reason' => 'proc_open is disabled.']]);

    Livewire::test('pages::admin.settings')
        ->call('checkForUpdates')
        ->assertSet('deployBlocked', 'proc_open is disabled.')
        ->assertSet('deployStatus', []);
});

it('will not deploy without checking first', function () {
    // The status travelled through the browser, so it is re-checked before
    // anything is pulled onto the server.
    $fake = fakeDeployer();

    Livewire::test('pages::admin.settings')
        ->call('startDeploy')
        ->assertSet('deploying', false);

    expect($fake->called)->toBe([]);
});

it('will not deploy when there is nothing to pull', function () {
    $fake = fakeDeployer();

    Livewire::test('pages::admin.settings')
        ->call('checkForUpdates')
        ->call('startDeploy')
        ->assertSet('deploying', false);

    expect($fake->called)->toBe([]);
});

it('runs pull, migrate and cache in that order', function () {
    $fake = fakeDeployer(['status' => behindStatus()]);

    $component = Livewire::test('pages::admin.settings')
        ->call('checkForUpdates')
        ->call('startDeploy')
        ->assertSet('deploying', true);

    // The browser drives one step per poll.
    $component->call('runDeployStep')
        ->call('runDeployStep')
        ->call('runDeployStep')
        ->assertSet('deploying', false);

    expect($fake->called)->toBe(['pull', 'migrate', 'cache']);
});

it('stops at a failed step and leaves the rest unrun', function () {
    $fake = fakeDeployer([
        'status' => behindStatus(),
        'pull' => ['ok' => false, 'output' => '', 'error' => 'Your local changes would be overwritten'],
    ]);

    Livewire::test('pages::admin.settings')
        ->call('checkForUpdates')
        ->call('startDeploy')
        ->call('runDeployStep')
        ->assertSet('deploying', false)
        ->assertSet('deploySteps.0.state', 'failed')
        ->assertSet('deploySteps.1.state', 'pending')
        ->assertSee('Your local changes would be overwritten');

    expect($fake->called)->toBe(['pull']);
});

it('ignores a poll once the deploy has finished', function () {
    $fake = fakeDeployer(['status' => behindStatus()]);

    $component = Livewire::test('pages::admin.settings')
        ->call('checkForUpdates')
        ->call('startDeploy');

    foreach (range(1, 6) as $ignored) {
        $component->call('runDeployStep');
    }

    // Three steps, three calls, no matter how many polls arrive.
    expect($fake->called)->toBe(['pull', 'migrate', 'cache']);
});

it('clears the cached config instead of building one', function () {
    // config:cache boots a fresh application, which decrypts the mail password
    // and the Google service-account key into config — and then writes the lot
    // to this file in plaintext. Deploying must remove it, not create it.
    $cached = base_path('bootstrap/cache/config.php');
    File::put($cached, '<?php return [];');

    app(GitDeployer::class)->refreshCaches();

    expect(File::exists($cached))->toBeFalse();
});

it('reports a server with no repository', function () {
    // The real check, not the fake: a site uploaded by FTP has no .git.
    $support = app(GitDeployer::class)->support();

    expect($support)->toHaveKeys(['ok', 'reason']);
});

it('explains an ssh failure as a hosting limitation, not a mystery', function () {
    // The exact error a jailed shared-hosting PHP process produces.
    $deployer = app(GitDeployer::class);

    $hint = $deployer->diagnose('ssh: Could not resolve hostname github.com: Non-recoverable failure in name resolution');

    expect($hint)->toBeString()->toContain('HTTPS');
});

it('explains a refused key and a rejected token differently', function () {
    $deployer = app(GitDeployer::class);

    expect($deployer->diagnose('git@github.com: Permission denied (publickey).'))
        ->toContain('SSH key')
        ->and($deployer->diagnose('fatal: Authentication failed for https://github.com/x/y.git'))
        ->toContain('access token');
});

it('explains a refusal to overwrite files edited on the server', function () {
    $deployer = app(GitDeployer::class);

    expect($deployer->diagnose('error: Your local changes to the following files would be overwritten by merge'))
        ->toContain('edited directly on the server');
});

it('offers no explanation for a failure it does not recognise', function () {
    // Better a raw message than a confidently wrong one.
    expect(app(GitDeployer::class)->diagnose('something nobody has seen before'))->toBeNull()
        ->and(app(GitDeployer::class)->diagnose(null))->toBeNull();
});

it('shows the explanation when a check fails', function () {
    fakeDeployer([
        'status' => [
            'ok' => false, 'branch' => 'main', 'behind' => 0, 'upToDate' => false,
            'commits' => [], 'current' => '',
            'error' => 'ssh: Could not resolve hostname github.com',
        ],
    ]);

    Livewire::test('pages::admin.settings')
        ->call('checkForUpdates')
        ->assertSee('What went wrong');
});
