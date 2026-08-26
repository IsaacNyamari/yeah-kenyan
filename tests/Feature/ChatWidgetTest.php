<?php

use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    Setting::putMany([
        'tawk_enabled' => '1',
        'tawk_property_id' => '67c0195edbf28e190997f139',
        'tawk_widget_id' => '1il366770',
    ]);
});

it('embeds the chat widget on the public site', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('embed.tawk.to/67c0195edbf28e190997f139/1il366770', escape: false);
});

it('embeds the widget on every public page', function (string $route) {
    $this->get(route($route))->assertOk()->assertSee('embed.tawk.to', escape: false);
})->with(['home', 'about', 'contact', 'gallery', 'news.index']);

it('hides the widget when the toggle is off', function () {
    Setting::putMany(['tawk_enabled' => '0']);

    $this->get(route('home'))->assertOk()->assertDontSee('embed.tawk.to', escape: false);
});

it('hides the widget when the ids are missing', function () {
    Setting::putMany(['tawk_property_id' => '', 'tawk_widget_id' => '']);

    $this->get(route('home'))->assertOk()->assertDontSee('embed.tawk.to', escape: false);
});

it('keeps the widget out of the admin area', function () {
    $this->actingAs(User::factory()->create());

    // The CMS uses the app layout, so visitors' live chat never loads there.
    $this->get(route('dashboard'))->assertOk()->assertDontSee('embed.tawk.to', escape: false);
});

it('only injects the script once across spa navigation', function () {
    // data-navigate-once stops Livewire re-running it on every wire:navigate.
    $this->get(route('home'))->assertOk()->assertSee('data-navigate-once', escape: false);
});
