<?php

use App\Models\Setting;
use App\Models\User;
use App\Providers\SettingsServiceProvider;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

it('is restricted to administrators', function () {
    auth()->logout();
    $this->get(route('admin.settings'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.settings'))->assertForbidden();

    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('admin.settings'))->assertOk();
});

it('saves mail settings from the dashboard', function () {
    Livewire::test('pages::admin.settings')
        ->set('site_name', 'Yeah Kenyan Events Limited')
        ->set('mail_host', 'mail.example.com')
        ->set('mail_port', '587')
        ->set('mail_username', 'user@example.com')
        ->set('mail_password', 'sup3r-secret')
        ->set('mail_encryption', 'tls')
        ->set('mail_from_address', 'info@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('mail_host'))->toBe('mail.example.com')
        ->and(Setting::get('mail_password'))->toBe('sup3r-secret');
});

/*
 * A database dump should not hand over the mail password or the Google
 * service-account key.
 */
it('encrypts secrets at rest', function () {
    Setting::putMany(['mail_password' => 'sup3r-secret']);

    $raw = DB::table('settings')->where('key', 'mail_password')->first();

    expect($raw->value)->not->toBe('sup3r-secret')
        ->and($raw->is_encrypted)->toBeTruthy()
        ->and(Crypt::decryptString($raw->value))->toBe('sup3r-secret')
        ->and(Setting::get('mail_password'))->toBe('sup3r-secret');
});

it('stores non-secret settings in the clear', function () {
    Setting::putMany(['mail_host' => 'mail.example.com']);

    $raw = DB::table('settings')->where('key', 'mail_host')->first();

    expect($raw->value)->toBe('mail.example.com')
        ->and($raw->is_encrypted)->toBeFalsy();
});

it('never sends stored secrets back to the browser', function () {
    Setting::putMany(['mail_password' => 'sup3r-secret']);

    Livewire::test('pages::admin.settings')
        ->assertSet('mail_password', '')
        ->assertDontSee('sup3r-secret');
});

it('keeps the stored secret when the field is left blank', function () {
    Setting::putMany(['mail_password' => 'original-secret']);

    Livewire::test('pages::admin.settings')
        ->set('site_name', 'Yeah Kenyan')
        ->set('mail_encryption', 'tls')
        ->set('mail_password', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('mail_password'))->toBe('original-secret');
});

it('replaces the secret when a new one is supplied', function () {
    Setting::putMany(['mail_password' => 'original-secret']);

    Livewire::test('pages::admin.settings')
        ->set('site_name', 'Yeah Kenyan')
        ->set('mail_encryption', 'tls')
        ->set('mail_password', 'rotated-secret')
        ->call('save');

    expect(Setting::get('mail_password'))->toBe('rotated-secret');
});

it('accepts an analytics property id without touching the env file', function () {
    Livewire::test('pages::admin.settings')
        ->set('site_name', 'Yeah Kenyan')
        ->set('mail_encryption', 'tls')
        ->set('analytics_property_id', '987654321')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('analytics_property_id'))->toBe('987654321');
});

it('rejects a non-numeric property id and malformed credentials json', function () {
    Livewire::test('pages::admin.settings')
        ->set('site_name', 'Yeah Kenyan')
        ->set('mail_encryption', 'tls')
        ->set('analytics_property_id', 'GA4-abc')
        ->set('analytics_credentials', 'not json at all')
        ->call('save')
        ->assertHasErrors(['analytics_property_id', 'analytics_credentials']);
});

it('feeds stored settings into the runtime config', function () {
    Setting::putMany([
        'mail_host' => 'mail.example.com',
        'mail_port' => '465',
        'mail_username' => 'user@example.com',
        'mail_password' => 'sup3r-secret',
        'mail_encryption' => 'ssl',
        'analytics_property_id' => '987654321',
        'site_name' => 'Configured From The CMS',
    ]);

    (new SettingsServiceProvider(app()))->boot();

    expect(config('mail.mailers.smtp.host'))->toBe('mail.example.com')
        ->and(config('mail.mailers.smtp.port'))->toBe(465)
        ->and(config('mail.mailers.smtp.password'))->toBe('sup3r-secret')
        // ssl means implicit TLS, which Symfony expresses as the smtps scheme.
        ->and(config('mail.mailers.smtp.scheme'))->toBe('smtps')
        ->and(config('analytics.property_id'))->toBe('987654321')
        ->and(config('site.name'))->toBe('Configured From The CMS');
});

it('boots cleanly when nothing has been configured', function () {
    Setting::query()->delete();
    Setting::flush();

    $host = config('mail.mailers.smtp.host');

    (new SettingsServiceProvider(app()))->boot();

    // Falls through to the config defaults rather than blanking them.
    expect(config('mail.mailers.smtp.host'))->toBe($host);
});

it('treats an undecryptable secret as unset rather than breaking', function () {
    // Simulates a rotated APP_KEY leaving old ciphertext unreadable.
    DB::table('settings')->updateOrInsert(
        ['key' => 'mail_password'],
        ['value' => 'not-valid-ciphertext', 'is_encrypted' => true],
    );

    Setting::flush();

    expect(Setting::get('mail_password'))->toBeNull();
});

it('writes uploads inside the web root so no symlink is needed', function () {
    $root = rtrim((string) config('filesystems.disks.public.root'), '/\\');

    expect($root)->toStartWith(rtrim(public_path(), '/\\'));
});
