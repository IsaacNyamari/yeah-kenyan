<?php

use App\Models\User;
use App\Services\Impersonator;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');
Route::livewire('about', 'pages::about')->name('about');
Route::livewire('gallery', 'pages::gallery')->name('gallery');
Route::livewire('contact', 'pages::contact')->name('contact');

Route::livewire('news', 'pages::news.index')->name('news.index');
Route::livewire('news/{slug}', 'pages::news.show')->name('news.show');

Route::livewire('newsletter/unsubscribe/{token}', 'pages::site.unsubscribe')->name('newsletter.unsubscribe');

/*
 * Every dashboard screen is gated on the permission for its own area, so an
 * account only reaches what it has been granted. The sidebar hides the same
 * entries, but the gate here is what actually enforces it.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::admin.dashboard')->name('dashboard');

    Route::livewire('admin/posts', 'pages::admin.posts')
        ->middleware('can:manage news')
        ->name('admin.posts');

    Route::livewire('admin/moderation', 'pages::admin.moderation')
        ->middleware('can:moderate posts')
        ->name('admin.moderation');

    Route::livewire('admin/gallery', 'pages::admin.gallery')
        ->middleware('can:manage gallery')
        ->name('admin.gallery');

    Route::livewire('admin/services', 'pages::admin.services')
        ->middleware('can:manage services')
        ->name('admin.services');

    Route::livewire('admin/classes', 'pages::admin.classes')
        ->middleware('can:manage classes')
        ->name('admin.classes');

    Route::livewire('admin/testimonials', 'pages::admin.testimonials')
        ->middleware('can:manage testimonials')
        ->name('admin.testimonials');

    Route::livewire('admin/messages', 'pages::admin.messages')
        ->middleware('can:manage messages')
        ->name('admin.messages');

    Route::livewire('admin/contact', 'pages::admin.contact')
        ->middleware('can:manage contact')
        ->name('admin.contact');

    Route::livewire('admin/subscribers', 'pages::admin.subscribers')
        ->middleware('can:manage subscribers')
        ->name('admin.subscribers');

    Route::livewire('admin/newsletters', 'pages::admin.newsletters')
        ->middleware('can:manage newsletters')
        ->name('admin.newsletters');

    Route::livewire('admin/newsletters/templates', 'pages::admin.newsletter-templates')
        ->middleware('can:manage newsletters')
        ->name('admin.newsletter-templates');

    Route::livewire('admin/newsletters/{newsletter}/send', 'pages::admin.newsletter-send')
        ->middleware('can:manage newsletters')
        ->name('admin.newsletter-send');

    Route::livewire('admin/users', 'pages::admin.users')
        ->middleware('can:manage roles')
        ->name('admin.users');

    /*
     * Impersonation. Starting is gated on the roles permission; stopping is
     * not, because the only session that can stop is one already holding an
     * administrator's id, and refusing it would strand them.
     */
    Route::post('admin/users/{user}/impersonate', function (User $user, Impersonator $impersonator) {
        $impersonator->start($user);

        return redirect()->route('dashboard');
    })->middleware('can:manage roles')->name('admin.impersonate');

    Route::post('impersonate/stop', function (Impersonator $impersonator) {
        return $impersonator->stop()
            ? redirect()->route('admin.users')
            : redirect()->route('dashboard');
    })->name('impersonate.stop');

    Route::livewire('admin/settings', 'pages::admin.settings')
        ->middleware('can:manage settings')
        ->name('admin.settings');

    Route::livewire('admin/analytics', 'pages::admin.analytics')
        ->middleware('can:view analytics')
        ->name('admin.analytics');
});

/*
 * Service and online-class pages share one template and keep the slugs the
 * legacy site used, so existing inbound links stay valid. Registered last so
 * the catch-all slug never shadows a named route above; unknown slugs 404
 * from inside the component.
 */
Route::livewire('{slug}', 'pages::detail')
    ->where('slug', '[a-z0-9-]+')
    ->name('page');

require __DIR__.'/settings.php';
