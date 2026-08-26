@props([
    'code',
    'title',
    'message',
    'icon' => 'exclamation-triangle',
])

{{--
    Deliberately standalone.

    The public site layout queries the database for navigation and settings,
    and a downed database is one of the likeliest reasons a 500 renders at all.
    This page therefore touches nothing but config, routes and static assets.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />

    <title>{{ $code }} — {{ $title }} | {{ config('site.name', config('app.name')) }}</title>

    <link rel="icon" href="/favicon.ico" sizes="48x48">
    <link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-200">

    <div class="flex min-h-screen flex-col">

        {{-- Masthead --}}
        <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto flex max-w-5xl items-center gap-3 px-6 py-5">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('site.name') }}" class="h-10 w-auto">
                    <span class="text-lg font-bold tracking-tight uppercase sm:text-xl">
                        <span class="text-brand-600">Yeah Kenyan</span>
                        <span class="text-leaf-600">Events Limited</span>
                    </span>
                </a>
            </div>
        </header>

        {{-- Body --}}
        <main class="flex flex-1 items-center justify-center px-6 py-16">
            <div class="w-full max-w-2xl text-center">

                <div class="relative inline-flex items-center justify-center">
                    <span class="text-[8rem] leading-none font-black tracking-tighter text-zinc-200 select-none sm:text-[11rem] dark:text-zinc-800">
                        {{ $code }}
                    </span>
                </div>

                <h1 class="mt-2 text-3xl font-bold tracking-tight uppercase sm:text-4xl">{{ $title }}</h1>

                <p class="mx-auto mt-4 max-w-lg leading-relaxed text-zinc-600 dark:text-zinc-400">
                    {{ $message }}
                </p>

                <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('home') }}"
                       class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                        Back to home
                    </a>
                    <a href="{{ route('contact') }}"
                       class="rounded-lg border border-zinc-300 px-5 py-2.5 text-sm font-semibold transition hover:border-zinc-400 hover:bg-white dark:border-zinc-700 dark:hover:bg-zinc-900">
                        Contact us
                    </a>
                </div>

                {{-- Anything else worth offering on this particular error --}}
                {{ $slot ?? '' }}

                <div class="mt-12 border-t border-zinc-200 pt-8 dark:border-zinc-800">
                    <p class="text-sm text-zinc-500">Or head straight to</p>
                    <div class="mt-3 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm">
                        <a href="{{ route('about') }}" class="transition hover:text-brand-600">About Us</a>
                        <a href="{{ route('gallery') }}" class="transition hover:text-brand-600">Gallery</a>
                        <a href="{{ route('news.index') }}" class="transition hover:text-brand-600">News</a>
                        <a href="{{ route('page', 'event-planning') }}" class="transition hover:text-brand-600">Event Planning</a>
                    </div>
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto max-w-5xl px-6 py-6 text-center text-sm text-zinc-500">
                &copy; {{ now()->year }} {{ config('site.name') }}. All Rights Reserved.
            </div>
        </footer>
    </div>
</body>
</html>
