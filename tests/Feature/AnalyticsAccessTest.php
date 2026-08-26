<?php

use App\Models\User;
use App\Services\AnalyticsReport;

it('redirects guests to the login screen', function () {
    $this->get(route('admin.analytics'))->assertRedirect(route('login'));
});

it('forbids a signed-in user who is not an administrator', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]));

    $this->get(route('admin.analytics'))->assertForbidden();
});

it('allows an administrator through', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get(route('admin.analytics'))->assertOk();
});

it('leaves the rest of the cms open to any signed-in user', function (string $route) {
    $this->actingAs(User::factory()->create(['is_admin' => false]));

    $this->get(route($route))->assertOk();
})->with(['dashboard', 'admin.posts', 'admin.services', 'admin.messages']);

it('shows the analytics link in the sidebar only for administrators', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get(route('dashboard'))->assertOk()->assertSee(route('admin.analytics'));

    auth()->logout();

    $this->actingAs(User::factory()->create(['is_admin' => false]));
    $this->get(route('dashboard'))->assertOk()->assertDontSee(route('admin.analytics'));
});

it('renders a setup guide instead of failing when google analytics is unconfigured', function () {
    config(['analytics.property_id' => null]);

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get(route('admin.analytics'))
        ->assertOk()
        ->assertSee('Google Analytics is not connected yet')
        // Configured through the CMS now, not by editing .env over SSH.
        ->assertSee('Open analytics settings')
        ->assertSee(route('admin.settings'), escape: false);
});

it('reports which prerequisites are missing', function () {
    config([
        'analytics.property_id' => null,
        'analytics.service_account_credentials_json' => '/nope/missing.json',
    ]);

    $report = app(AnalyticsReport::class);

    expect($report->isConfigured())->toBeFalse()
        ->and($report->readiness())->toBe(['property_id' => false, 'credentials' => false]);
});

it('returns empty data rather than throwing when unconfigured', function () {
    config(['analytics.property_id' => null]);

    $report = app(AnalyticsReport::class);

    expect($report->topPages(30))->toBeEmpty()
        ->and($report->topReferrers(30))->toBeEmpty()
        ->and($report->totals(30))->toBe(['visitors' => 0, 'pageViews' => 0]);
});
