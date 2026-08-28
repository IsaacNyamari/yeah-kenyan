<?php

use App\Models\Setting;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Support\Facades\Gate;

/*
 * Two site-wide switches: whether anyone may sign up, and whether authors and
 * moderators may post. Both default to on so an existing install is unchanged
 * until an administrator says otherwise.
 */

it('leaves registration open by default', function () {
    $this->get(route('register'))->assertOk();
});

it('closes the sign-up form when registration is switched off', function () {
    Setting::putMany(['registration_enabled' => false]);

    $this->get(route('register'))->assertForbidden();
});

it('closes the endpoint behind the form, not just the form', function () {
    // A page left open in a tab would otherwise still be able to create an
    // account after registration was closed.
    Setting::putMany(['registration_enabled' => false]);

    $this->post(route('register.store'), [
        'name' => 'Uninvited',
        'email' => 'uninvited@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertForbidden();

    expect(User::where('email', 'uninvited@example.com')->exists())->toBeFalse();
});

it('hides the sign-up link once registration is closed', function () {
    $this->get(route('login'))->assertSee(route('register'), escape: false);

    Setting::putMany(['registration_enabled' => false]);

    $this->get(route('login'))->assertDontSee(route('register'), escape: false);
});

it('leaves login alone when registration is closed', function () {
    Setting::putMany(['registration_enabled' => false]);

    $this->get(route('login'))->assertOk();
});

it('lets an author post while posting is open', function () {
    expect(Gate::forUser(User::factory()->create())->allows('post-content'))->toBeTrue();
});

it('stops authors and moderators posting once posting is switched off', function () {
    Setting::putMany(['posting_enabled' => false]);

    expect(Gate::forUser(User::factory()->create())->allows('post-content'))->toBeFalse()
        ->and(Gate::forUser(User::factory()->moderator()->create())->allows('post-content'))->toBeFalse();
});

it('keeps administrators posting when posting is switched off', function () {
    // Otherwise pausing submissions would leave nobody able to publish.
    Setting::putMany(['posting_enabled' => false]);

    expect(Gate::forUser(User::factory()->admin()->create())->allows('post-content'))->toBeTrue();
});

it('only lets moderators and administrators moderate', function () {
    expect(Gate::forUser(User::factory()->create())->allows('moderate-content'))->toBeFalse()
        ->and(Gate::forUser(User::factory()->moderator()->create())->allows('moderate-content'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->admin()->create())->allows('moderate-content'))->toBeTrue();
});

it('makes a newly registered account an author', function () {
    $this->post(route('register.store'), [
        'name' => 'Fresh Author',
        'email' => 'fresh@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::where('email', 'fresh@example.com')->first()?->primaryRole())
        ->toBe(UserRole::Author);
});
