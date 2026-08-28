@php
    $services = \App\Models\Page::navigation(\App\Models\Page::TYPE_SERVICE);
    $classes = \App\Models\Page::navigation(\App\Models\Page::TYPE_CLASS);
@endphp

<header x-data="{ open: false, services: false, classes: false }" class="sticky top-0 z-50">
    {{-- Utility bar --}}
    <div class="hidden bg-zinc-900 text-zinc-300 lg:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-2 text-xs">
            <div class="flex items-center divide-x divide-zinc-700">
                <span class="pr-4">{{ now()->format('D M d, Y') }}</span>
                <a wire:navigate href="{{ route('contact') }}" class="px-4 transition hover:text-white">Advertise</a>
                <a wire:navigate href="{{ route('contact') }}" class="px-4 transition hover:text-white">Contact</a>
                <a wire:navigate href="{{ route('news.index') }}" class="pl-4 transition hover:text-white">News</a>

                {{--
                    Plain links, not wire:navigate: these cross from the public
                    layout into the admin and auth layouts, which load different
                    assets. A full page load is the reliable way over.
                --}}
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 transition hover:text-white">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="px-4 transition hover:text-white">Admin Login</a>
                @endauth
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ \App\Models\Setting::get('social_facebook') }}" target="_blank" rel="noopener" aria-label="Facebook" class="transition hover:text-white">
                    <x-site.social-icon name="facebook" class="size-4" />
                </a>
                <a href="{{ \App\Models\Setting::get('social_instagram') }}" target="_blank" rel="noopener" aria-label="Instagram" class="transition hover:text-white">
                    <x-site.social-icon name="instagram" class="size-4" />
                </a>
                <a href="{{ \App\Models\Setting::get('social_youtube') }}" target="_blank" rel="noopener" aria-label="YouTube" class="transition hover:text-white">
                    <x-site.social-icon name="youtube" class="size-4" />
                </a>
            </div>
        </div>
    </div>

    {{-- Brand bar --}}
    <div class="hidden border-b border-zinc-100 bg-white lg:block dark:border-zinc-800 dark:bg-zinc-950">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-6 py-5">
            <a wire:navigate href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('site.name') }}" class="h-12 w-auto">
                <span class="text-2xl font-bold tracking-tight uppercase">
                    <span class="text-brand-600">Yeah Kenyan</span>
                    <span class="text-leaf-600">Events Limited</span>
                </span>
            </a>
        </div>
    </div>

    {{-- Main navigation --}}
    <nav class="bg-zinc-900 text-zinc-200 shadow-lg">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6">

            <a wire:navigate href="{{ route('home') }}" class="flex items-center gap-2 py-3 lg:hidden">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('site.name') }}" class="h-9 w-auto">
                <span class="text-lg font-bold uppercase">
                    <span class="text-brand-500">Yeah</span> <span class="text-leaf-500">Kenyan</span>
                </span>
            </a>

            <button type="button" @click="open = !open" class="p-2 lg:hidden" aria-label="Toggle navigation">
                <flux:icon.bars-3 x-show="!open" class="size-6" />
                <flux:icon.x-mark x-show="open" x-cloak class="size-6" />
            </button>

            {{-- Desktop links --}}
            <div class="hidden items-center gap-1 lg:flex">
                <x-site.nav-link :href="route('home')" :active="request()->routeIs('home')">Home</x-site.nav-link>
                <x-site.nav-link :href="route('about')" :active="request()->routeIs('about')">About Us</x-site.nav-link>

                <div class="relative" @mouseenter="services = true" @mouseleave="services = false">
                    <button type="button" class="flex items-center gap-1 px-4 py-4 text-sm font-medium transition hover:text-brand-400">
                        Services <flux:icon.chevron-down class="size-4" />
                    </button>
                    <div x-show="services" x-cloak x-transition.opacity
                         class="absolute left-0 z-50 w-72 border-t-2 border-brand-600 bg-white py-2 shadow-xl dark:bg-zinc-900">
                        @foreach ($services as $page)
                            <a wire:navigate href="{{ route('page', $page->slug) }}"
                               class="block px-4 py-2 text-sm text-zinc-700 transition hover:bg-brand-50 hover:text-brand-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                {{ $page->nav }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="relative" @mouseenter="classes = true" @mouseleave="classes = false">
                    <button type="button" class="flex items-center gap-1 px-4 py-4 text-sm font-medium transition hover:text-brand-400">
                        Online Classes <flux:icon.chevron-down class="size-4" />
                    </button>
                    <div x-show="classes" x-cloak x-transition.opacity
                         class="absolute left-0 z-50 w-64 border-t-2 border-leaf-600 bg-white py-2 shadow-xl dark:bg-zinc-900">
                        @foreach ($classes as $page)
                            <a wire:navigate href="{{ route('page', $page->slug) }}"
                               class="block px-4 py-2 text-sm text-zinc-700 transition hover:bg-leaf-50 hover:text-leaf-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                {{ $page->nav }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <x-site.nav-link :href="route('gallery')" :active="request()->routeIs('gallery')">Gallery</x-site.nav-link>
                <x-site.nav-link :href="route('news.index')" :active="request()->routeIs('news.*')">News</x-site.nav-link>
                <x-site.nav-link :href="route('contact')" :active="request()->routeIs('contact')">Contact</x-site.nav-link>

                @auth
                    <a href="{{ route('dashboard') }}"
                       class="ms-3 flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                        <flux:icon.squares-2x2 class="size-4" />
                        Dashboard
                    </a>
                @else
                    <div class="ms-3 flex items-center gap-2">
                        <a href="{{ route('login') }}"
                           class="rounded-lg px-3 py-2 text-sm font-medium transition hover:text-brand-400">
                            Log in
                        </a>
                        @if (\App\Models\Setting::boolean('registration_enabled', true))
                            <a href="{{ route('register') }}"
                               class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                                Sign up
                            </a>
                        @endif
                    </div>
                @endauth
            </div>
        </div>

        {{-- Mobile menu --}}
        <div x-show="open" x-cloak x-collapse class="border-t border-zinc-800 lg:hidden">
            <div class="space-y-1 px-6 py-4">
                <a wire:navigate href="{{ route('home') }}" class="block py-2 text-sm font-medium">Home</a>
                <a wire:navigate href="{{ route('about') }}" class="block py-2 text-sm font-medium">About Us</a>

                <details class="group">
                    <summary class="flex cursor-pointer list-none items-center justify-between py-2 text-sm font-medium">
                        Services <flux:icon.chevron-down class="size-4 transition group-open:rotate-180" />
                    </summary>
                    <div class="mt-1 space-y-1 border-l border-zinc-700 pl-4">
                        @foreach ($services as $page)
                            <a wire:navigate href="{{ route('page', $page->slug) }}" class="block py-1.5 text-sm text-zinc-400">{{ $page->nav }}</a>
                        @endforeach
                    </div>
                </details>

                <details class="group">
                    <summary class="flex cursor-pointer list-none items-center justify-between py-2 text-sm font-medium">
                        Online Classes <flux:icon.chevron-down class="size-4 transition group-open:rotate-180" />
                    </summary>
                    <div class="mt-1 space-y-1 border-l border-zinc-700 pl-4">
                        @foreach ($classes as $page)
                            <a wire:navigate href="{{ route('page', $page->slug) }}" class="block py-1.5 text-sm text-zinc-400">{{ $page->nav }}</a>
                        @endforeach
                    </div>
                </details>

                <a wire:navigate href="{{ route('gallery') }}" class="block py-2 text-sm font-medium">Gallery</a>
                <a wire:navigate href="{{ route('news.index') }}" class="block py-2 text-sm font-medium">News</a>
                <a wire:navigate href="{{ route('contact') }}" class="block py-2 text-sm font-medium">Contact</a>

                <div class="mt-3 border-t border-zinc-800 pt-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white">
                            <flux:icon.squares-2x2 class="size-4" />
                            Dashboard
                        </a>
                    @else
                        <div class="flex items-center gap-2">
                            <a href="{{ route('login') }}"
                               class="flex-1 rounded-lg border border-zinc-700 px-4 py-2.5 text-center text-sm font-medium">
                                Log in
                            </a>
                            @if (\App\Models\Setting::boolean('registration_enabled', true))
                                <a href="{{ route('register') }}"
                                   class="flex-1 rounded-lg bg-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white">
                                    Sign up
                                </a>
                            @endif
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
</header>
