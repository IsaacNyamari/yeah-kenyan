<?php

use App\Models\Subscriber;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('adds someone by hand', function () {
    Livewire::test('pages::admin.subscribers')
        ->set('newEmail', 'walkin@example.com')
        ->set('newName', 'Walk In')
        ->call('add')
        ->assertHasNoErrors();

    expect(Subscriber::firstWhere('email', 'walkin@example.com')?->name)->toBe('Walk In');
});

it('refuses a duplicate address', function () {
    Subscriber::factory()->create(['email' => 'already@example.com']);

    Livewire::test('pages::admin.subscribers')
        ->set('newEmail', 'already@example.com')
        ->call('add')
        ->assertHasErrors(['newEmail' => 'unique']);
});

it('unsubscribes without losing the record', function () {
    // A deleted address can sign up again through the public form and start
    // receiving mail, which is what the person asked not to happen.
    $subscriber = Subscriber::factory()->create();

    Livewire::test('pages::admin.subscribers')->call('unsubscribe', $subscriber->id);

    expect($subscriber->fresh()->isSubscribed())->toBeFalse()
        ->and(Subscriber::whereKey($subscriber->id)->exists())->toBeTrue();
});

it('puts someone back on the list', function () {
    $subscriber = Subscriber::factory()->unsubscribed()->create();

    Livewire::test('pages::admin.subscribers')->call('resubscribe', $subscriber->id);

    expect($subscriber->fresh()->isSubscribed())->toBeTrue();
});

it('counts only current subscribers as the audience', function () {
    Subscriber::factory()->count(3)->create();
    Subscriber::factory()->unsubscribed()->count(2)->create();

    Livewire::test('pages::admin.subscribers')
        ->assertSet('counts.subscribed', 3)
        ->assertSet('counts.unsubscribed', 2)
        ->assertSet('counts.total', 5);
});

it('gives every subscriber an unsubscribe token', function () {
    $subscriber = Subscriber::create(['email' => 'tokened@example.com']);

    expect($subscriber->token)->not->toBeEmpty();
});

it('lets someone unsubscribe from the link without signing in', function () {
    auth()->logout();

    $subscriber = Subscriber::factory()->create();

    Livewire::test('pages::site.unsubscribe', ['token' => $subscriber->token])
        ->assertOk()
        ->call('unsubscribe')
        ->assertSet('done', true);

    expect($subscriber->fresh()->isSubscribed())->toBeFalse();
});

it('shows a plain message for an unrecognised token', function () {
    auth()->logout();

    Livewire::test('pages::site.unsubscribe', ['token' => 'not-a-real-token'])
        ->assertOk()
        ->assertSee('Link not recognised');
});

it('keeps the public signup working', function () {
    auth()->logout();

    Livewire::test('pages::site.newsletter')
        ->set('email', 'newcomer@example.com')
        ->call('subscribe')
        ->assertHasNoErrors();

    expect(Subscriber::firstWhere('email', 'newcomer@example.com')?->token)->not->toBeEmpty();
});
