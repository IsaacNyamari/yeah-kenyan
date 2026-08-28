<?php

use App\Models\Setting;
use App\Models\User;

/*
 * Two separate things share the "analytics" name: the gtag snippet that
 * records visits, and the Data API credentials that read them back for the
 * dashboard. These cover the former.
 */

beforeEach(function () {
    Setting::putMany([
        'analytics_tracking_enabled' => true,
        'analytics_measurement_id' => 'G-HQWMGQX00T',
    ]);

    // The component deliberately stays silent outside production.
    app()->detectEnvironment(fn (): string => 'production');
});

function analyticsTag(): string
{
    return view('components.site.analytics-tag')->render();
}

it('emits the google tag with the configured measurement id', function () {
    expect(analyticsTag())
        ->toContain('googletagmanager.com/gtag/js?id=G-HQWMGQX00T')
        ->toContain('G-HQWMGQX00T');
});

it('stays out of local and test environments', function (string $environment) {
    app()->detectEnvironment(fn (): string => $environment);

    expect(analyticsTag())->not->toContain('googletagmanager');
})->with(['local', 'testing']);

it('emits nothing while tracking is switched off', function () {
    Setting::putMany(['analytics_tracking_enabled' => false]);

    expect(analyticsTag())->not->toContain('googletagmanager');
});

it('emits nothing without a measurement id', function () {
    Setting::putMany(['analytics_measurement_id' => '']);

    expect(analyticsTag())->not->toContain('googletagmanager');
});

/*
 * wire:navigate swaps the page without a document load, so gtag's automatic
 * pageview would only ever record the landing page.
 */
it('reports a pageview on spa navigation instead of relying on the automatic one', function () {
    expect(analyticsTag())
        ->toContain('send_page_view: false')
        ->toContain("addEventListener('livewire:navigated'")
        ->toContain("gtag('event', 'page_view'");
});

it('loads the tag once rather than on every navigation', function () {
    expect(substr_count(analyticsTag(), 'data-navigate-once'))->toBe(2);
});

it('tracks the public site', function () {
    $this->get(route('home'))->assertOk()->assertSee('googletagmanager', escape: false);
});

it('does not track the admin area', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))->assertOk()->assertDontSee('googletagmanager', escape: false);
});

it('rejects a measurement id that is not in the G- format', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire\Livewire::test('pages::admin.settings')
        ->set('site_name', 'Yeah Kenyan')
        ->set('mail_encryption', 'tls')
        ->set('analytics_measurement_id', '123456789')
        ->call('save')
        ->assertHasErrors('analytics_measurement_id');
});

it('keeps the measurement id and the reporting property id apart', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire\Livewire::test('pages::admin.settings')
        ->set('site_name', 'Yeah Kenyan')
        ->set('mail_encryption', 'tls')
        ->set('analytics_measurement_id', 'G-HQWMGQX00T')
        ->set('analytics_property_id', '987654321')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('analytics_measurement_id'))->toBe('G-HQWMGQX00T')
        ->and(Setting::get('analytics_property_id'))->toBe('987654321');
});
