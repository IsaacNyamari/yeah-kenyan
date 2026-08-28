<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <x-site.analytics-tag />

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ filled($title ?? null) ? $title.' - '.config('site.name') : config('site.name') }}</title>

    <meta name="description" content="{{ $description ?? config('site.meta.description') }}" />
    <meta name="keywords" content="{{ config('site.meta.keywords') }}" />
    <meta name="author" content="{{ config('site.name') }}" />
    <meta name="robots" content="index, follow" />

    <meta property="og:title" content="{{ $title ?? config('site.name') }}" />
    <meta property="og:description" content="{{ $description ?? config('site.meta.description') }}" />
    <meta property="og:image" content="{{ asset('images/logo.png') }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="website" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:site" content="@YeahKenyan" />

    <link rel="icon" href="/favicon.ico" sizes="48x48">
    <link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="/icon-192.png" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-white font-sans text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-200">

    <x-site.header />

    <main>
        {{ $slot }}
    </main>

    <x-site.footer />

    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>

    <x-site.tawk-widget />

    <x-page-loader />

    @fluxScripts
</body>
</html>
