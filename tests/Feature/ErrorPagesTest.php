<?php

use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('renders every error page with the site branding', function (int $code) {
    $html = view("errors.$code", ['exception' => new HttpException($code, '')])->render();

    expect($html)
        ->toContain('images/logo.png')
        ->toContain('Yeah Kenyan')
        ->toContain((string) $code)
        ->toContain('favicon.ico');
})->with([403, 404, 419, 429, 500, 503]);

it('serves a branded 404 for an unknown url', function () {
    $this->get('/no-such-page-anywhere')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('images/logo.png', escape: false)
        ->assertSee('Yeah Kenyan');
});

it('serves a branded 403 when a non-admin opens analytics', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]));

    $this->get(route('admin.analytics'))
        ->assertForbidden()
        ->assertSee('Access denied')
        ->assertSee('images/logo.png', escape: false);
});

it('surfaces the reason on a 403 when one was given', function () {
    $this->actingAs(User::factory()->create(['is_admin' => false]));

    $this->get(route('admin.analytics'))
        ->assertForbidden()
        ->assertSee('This area is restricted to administrators.');
});

it('falls back to generic copy when a 403 carries no message', function () {
    $html = view('errors.403', ['exception' => new HttpException(403, '')])->render();

    expect($html)->toContain('You do not have permission to view this page');
});

it('renders a 403 even with no exception in scope', function () {
    // Guards against "Undefined variable $exception" if the view is rendered directly.
    expect(view('errors.403')->render())->toContain('Access denied');
});

it('keeps error pages out of search results', function () {
    $this->get('/no-such-page-anywhere')
        ->assertNotFound()
        ->assertSee('noindex, nofollow', escape: false);
});

/*
 * A downed database is a likely cause of a 500, and the public site layout
 * queries the database for navigation and settings. The error pages therefore
 * must not go anywhere near it.
 */
it('renders error pages without touching the database', function (int $code) {
    $queries = [];

    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $html = view("errors.$code", ['exception' => new HttpException($code, '')])->render();

    expect($queries)->toBeEmpty('Error page ran queries: '.implode('; ', $queries))
        ->and($html)->toContain('Yeah Kenyan');
})->with([403, 404, 500, 503]);

it('offers a route back into the site from an error page', function () {
    $html = view('errors.404', ['exception' => new HttpException(404, '')])->render();

    expect($html)
        ->toContain(route('home'))
        ->toContain(route('contact'))
        ->toContain(route('gallery'))
        ->toContain(route('news.index'));
});
