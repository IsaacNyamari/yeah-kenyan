<?php

use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Providers\SettingsServiceProvider;
use Livewire\Livewire;

/*
 * Timestamps are written in UTC and converted only when shown. Moving
 * app.timezone instead would leave rows saved before the change three hours
 * behind rows saved after it, with nothing recording which is which.
 */

it('keeps storage in utc regardless of the display timezone', function () {
    Setting::putMany(['site_timezone' => 'Africa/Nairobi']);
    (new SettingsServiceProvider(app()))->boot();

    $post = Post::factory()->create(['published_at' => '2026-08-26 18:09:56']);

    expect($post->fresh()->published_at->timezoneName)->toBe('UTC')
        ->and(config('app.timezone') ?: 'UTC')->toBe('UTC');
});

it('converts to the configured zone for display', function () {
    config(['site.timezone' => 'Africa/Nairobi']);

    $post = Post::factory()->create(['published_at' => '2026-08-26 18:09:56']);

    expect(site_time($post->published_at)?->format('H:i'))->toBe('21:09')
        ->and(site_time($post->published_at)?->timezoneName)->toBe('Africa/Nairobi');
});

it('follows the setting when the zone changes', function (string $zone, string $expected) {
    config(['site.timezone' => $zone]);

    $post = Post::factory()->create(['published_at' => '2026-08-26 12:00:00']);

    expect(site_time($post->published_at)?->format('H:i'))->toBe($expected);
})->with([
    ['UTC', '12:00'],
    ['Africa/Nairobi', '15:00'],
    ['America/New_York', '08:00'],
    ['Asia/Tokyo', '21:00'],
]);

it('saves the timezone from the settings screen', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test('pages::admin.settings')
        ->set('site_name', 'Yeah Kenyan')
        ->set('mail_encryption', 'tls')
        ->set('site_timezone', 'Africa/Nairobi')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('site_timezone'))->toBe('Africa/Nairobi');
});

it('rejects a timezone that does not exist', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test('pages::admin.settings')
        ->set('site_name', 'Yeah Kenyan')
        ->set('mail_encryption', 'tls')
        ->set('site_timezone', 'Mars/Olympus_Mons')
        ->call('save')
        ->assertHasErrors('site_timezone');
});

it('offers every iana zone grouped by region, africa first', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $zones = Livewire::test('pages::admin.settings')->get('timezones');

    expect(array_key_first($zones))->toBe('Africa')
        ->and($zones['Africa'])->toHaveKey('Africa/Nairobi')
        ->and($zones['Africa']['Africa/Nairobi'])->toContain('UTC+03:00')
        ->and($zones)->toHaveKeys(['Africa', 'America', 'Asia', 'Europe']);
});

it('pushes the stored zone into the runtime config', function () {
    Setting::putMany(['site_timezone' => 'Asia/Tokyo']);

    (new SettingsServiceProvider(app()))->boot();

    expect(config('site.timezone'))->toBe('Asia/Tokyo');
});

it('shows converted times on the public news pages', function () {
    config(['site.timezone' => 'Africa/Nairobi']);

    // 23:30 UTC is already the next day in Nairobi.
    $post = Post::factory()->create([
        'slug' => 'late-night-post',
        'published_at' => now()->subDays(2)->setTime(23, 30),
    ]);

    $this->get(route('news.show', $post->slug))
        ->assertOk()
        ->assertSee(site_time(now()->subDays(2)->setTime(23, 30))?->format('M d, Y'));
});

it('shows converted times in the admin inbox', function () {
    config(['site.timezone' => 'Africa/Nairobi']);

    $this->actingAs(User::factory()->create());

    $message = ContactMessage::factory()->create(['created_at' => '2026-08-26 18:09:56']);

    Livewire::test('pages::admin.messages')
        ->call('select', $message->id)
        ->assertSee('21:09');
});

it('falls back to utc when no zone is configured', function () {
    config(['site.timezone' => null]);

    $post = Post::factory()->create(['published_at' => '2026-08-26 12:00:00']);

    expect(site_time($post->published_at)?->format('H:i'))->toBe('12:00');
});
