<?php

use App\Models\User;
use App\Services\AppVersion;
use Illuminate\Support\Facades\File;

/*
 * The version lives in a VERSION file so the site can report what it is
 * running however the code arrived — a pull, an FTP upload, an archive — and
 * on a host where PHP cannot start git at all.
 */

it('reads the installed version from the VERSION file', function () {
    expect(app(AppVersion::class)->current())->toMatch('/^\d+\.\d+\.\d+$/');
});

it('is shipped with a VERSION file', function () {
    // Without it every install reports 0.0.0 and the deploy screen has nothing
    // to compare against.
    expect(File::exists(base_path('VERSION')))->toBeTrue();
});

it('treats a tag and a bare number as the same version', function () {
    $version = app(AppVersion::class);

    expect($version->normalise('v1.2.3'))->toBe('1.2.3')
        ->and($version->normalise(' 1.2.3 '))->toBe('1.2.3')
        ->and($version->compare('v'.$version->current()))->toBe(0);
});

it('knows when a release is newer', function () {
    $version = app(AppVersion::class);
    [$major, $minor, $patch] = array_map('intval', explode('.', $version->current()));

    expect($version->isBehind(sprintf('v%d.%d.%d', $major, $minor, $patch + 1)))->toBeTrue()
        ->and($version->isBehind(sprintf('%d.%d.%d', $major, $minor, $patch)))->toBeFalse()
        ->and($version->isBehind(sprintf('%d.%d.0', max(0, $major - 1), $minor)))->toBeFalse();
});

it('ignores a tag that is not a version', function () {
    // The repository carries a tag named after the project, and there is no
    // sensible way to say whether that is newer than what is installed.
    $version = app(AppVersion::class);

    expect($version->isValid('yeah_keanyan'))->toBeFalse()
        ->and($version->compare('yeah_keanyan'))->toBe(0)
        ->and($version->isBehind('yeah_keanyan'))->toBeFalse();
});

it('falls back rather than reporting a malformed version', function () {
    $path = base_path('VERSION');
    $original = File::get($path);

    try {
        File::put($path, "not-a-version\n");

        // A fresh instance: the real one caches after the first read.
        expect((new AppVersion)->current())->toBe('0.0.0');
    } finally {
        File::put($path, $original);
    }
});

it('offers the next version for each kind of change', function () {
    $steps = app(AppVersion::class)->nextVersions();

    expect($steps)->toHaveKeys(['patch', 'minor', 'major']);

    $version = app(AppVersion::class);

    foreach ($steps as $next) {
        expect($version->isBehind($next))->toBeTrue();
    }
});

it('shows the installed version on the deployment screen', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('v'.app(AppVersion::class)->current());
});

it('reports an update only when the released version is newer', function () {
    $this->actingAs(User::factory()->admin()->create());

    $version = app(AppVersion::class);
    [$major, $minor, $patch] = array_map('intval', explode('.', $version->current()));

    $component = Livewire\Livewire::test('pages::admin.settings');

    $component->set('deployStatus', ['latestVersion' => sprintf('v%d.%d.%d', $major, $minor, $patch + 1)]);
    expect($component->instance()->releaseAvailable)->toBeTrue();

    $component->set('deployStatus', ['latestVersion' => $version->current()]);
    expect($component->instance()->releaseAvailable)->toBeFalse();

    // A name rather than a version must not read as an update.
    $component->set('deployStatus', ['latestVersion' => 'yeah_keanyan']);
    expect($component->instance()->releaseAvailable)->toBeFalse();
});
