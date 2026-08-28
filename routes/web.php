<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');
Route::livewire('about', 'pages::about')->name('about');
Route::livewire('gallery', 'pages::gallery')->name('gallery');
Route::livewire('contact', 'pages::contact')->name('contact');

Route::livewire('news', 'pages::news.index')->name('news.index');
Route::livewire('news/{slug}', 'pages::news.show')->name('news.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::admin.dashboard')->name('dashboard');

    Route::livewire('admin/posts', 'pages::admin.posts')->name('admin.posts');
    Route::livewire('admin/gallery', 'pages::admin.gallery')->name('admin.gallery');
    Route::livewire('admin/services', 'pages::admin.services')->name('admin.services');
    Route::livewire('admin/classes', 'pages::admin.classes')->name('admin.classes');
    Route::livewire('admin/testimonials', 'pages::admin.testimonials')->name('admin.testimonials');
    Route::livewire('admin/messages', 'pages::admin.messages')->name('admin.messages');
    Route::livewire('admin/contact', 'pages::admin.contact')->name('admin.contact');

    // Roles decide what everyone else may do, so administrators only.
    Route::livewire('admin/users', 'pages::admin.users')
        ->middleware('admin')
        ->name('admin.users');

    // Credentials live here, so administrators only.
    Route::livewire('admin/settings', 'pages::admin.settings')
        ->middleware('admin')
        ->name('admin.settings');

    // Traffic data is administrator-only.
    Route::livewire('admin/analytics', 'pages::admin.analytics')
        ->middleware('admin')
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
