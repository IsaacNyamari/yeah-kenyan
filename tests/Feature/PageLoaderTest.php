<?php

use App\Models\User;

/*
 * The brand loader is mounted in the layouts rather than per page, so these
 * cover one page from each layout instead of every route.
 */

it('shows the loader on the public site', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('id="page-preloader"', escape: false)
        ->assertSee('images/loader.gif', escape: false);
});

it('shows the loader on the dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('id="page-preloader"', escape: false)
        ->assertSee('images/loader.gif', escape: false);
});

it('shows the loader on the auth screens', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('id="page-preloader"', escape: false);
});

it('carries the livewire activity badge alongside the overlay', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('id="livewire-activity"', escape: false);
});

it('hides the overlay when javascript is unavailable', function () {
    // Without the script there is nothing to dismiss it, so the markup has to
    // opt out rather than trap the visitor behind a permanent overlay.
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<noscript>', escape: false)
        ->assertSee('#page-preloader { display: none; }', escape: false);
});

it('ships the loader image', function () {
    expect(public_path('images/loader.gif'))->toBeFile();
});
