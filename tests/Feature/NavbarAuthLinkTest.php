<?php

use App\Models\User;

/*
 * The public navbar offers a way into the CMS: the dashboard when signed in,
 * log in / sign up otherwise.
 */

it('offers log in and sign up to a visitor', function () {
    $response = $this->get(route('home'))->assertOk();

    $response->assertSee(route('login'), escape: false)
        ->assertSee(route('register'), escape: false)
        ->assertSee('Sign up')
        ->assertDontSee(route('dashboard'), escape: false);
});

it('offers the dashboard once signed in', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('home'))->assertOk();

    $response->assertSee(route('dashboard'), escape: false)
        ->assertSee('Dashboard')
        ->assertDontSee(route('register'), escape: false);
});

it('shows the link on every public page', function (string $route) {
    $this->get(route($route))->assertOk()->assertSee(route('login'), escape: false);
})->with(['home', 'about', 'gallery', 'contact', 'news.index']);

it('links straight through to a working page', function () {
    // A dead link in the navbar would be worse than none at all.
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();

    $this->actingAs(User::factory()->create());
    $this->get(route('dashboard'))->assertOk();
});
