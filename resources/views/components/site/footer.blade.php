@php
    $services = \App\Models\Page::navigation(\App\Models\Page::TYPE_SERVICE)->take(6);
    $classes = \App\Models\Page::navigation(\App\Models\Page::TYPE_CLASS);
@endphp

<footer class="mt-20 bg-zinc-900 text-zinc-400">
    {{-- Newsletter --}}
    <div class="border-b border-zinc-800">
        <div class="mx-auto max-w-7xl px-6 py-12">
            <div class="grid items-center gap-8 md:grid-cols-2">
                <div>
                    <h2 class="text-2xl font-bold text-white">Newsletter</h2>
                    <p class="mt-2 text-sm">Subscribe to our weekly newsletter and get updated.</p>
                </div>
                <livewire:pages::site.newsletter />
            </div>
        </div>
    </div>

    {{-- Link columns --}}
    <div class="mx-auto max-w-7xl px-6 py-12">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">

            <div>
                <a wire:navigate href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('site.name') }}" class="h-10 w-auto">
                    <span class="text-lg font-bold uppercase">
                        <span class="text-brand-500">Yeah</span> <span class="text-leaf-500">Kenyan</span>
                    </span>
                </a>
                <p class="mt-4 text-sm leading-relaxed">
                    Creating unforgettable experiences since {{ config('site.founded') }}. Event technology,
                    documentation and production across Kenya.
                </p>
                <p class="mt-4 text-sm font-semibold text-leaf-500">{{ config('site.slogan') }}</p>
            </div>

            <div>
                <h3 class="mb-4 font-bold tracking-wide text-white uppercase">Our Services</h3>
                <ul class="space-y-2 text-sm">
                    @foreach ($services as $page)
                        <li>
                            <a wire:navigate href="{{ route('page', $page->slug) }}" class="transition hover:text-brand-400">
                                {{ $page->nav }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h3 class="mb-4 font-bold tracking-wide text-white uppercase">Online Classes</h3>
                <ul class="space-y-2 text-sm">
                    @foreach ($classes as $page)
                        <li>
                            <a wire:navigate href="{{ route('page', $page->slug) }}" class="transition hover:text-leaf-400">
                                {{ $page->nav }}
                            </a>
                        </li>
                    @endforeach
                    <li><a wire:navigate href="{{ route('gallery') }}" class="transition hover:text-leaf-400">Gallery</a></li>
                    <li><a wire:navigate href="{{ route('news.index') }}" class="transition hover:text-leaf-400">News</a></li>
                </ul>
            </div>

            <div>
                <h3 class="mb-4 font-bold tracking-wide text-white uppercase">Get In Touch</h3>
                <ul class="space-y-3 text-sm">
                    <li class="flex items-start gap-3">
                        <flux:icon.map-pin class="mt-0.5 size-4 shrink-0 text-brand-500" />
                        <span>{{ \App\Models\Setting::get('contact_address') }}</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <flux:icon.envelope class="mt-0.5 size-4 shrink-0 text-brand-500" />
                        <a href="mailto:{{ \App\Models\Setting::get('contact_email') }}" class="transition hover:text-white">
                            {{ \App\Models\Setting::get('contact_email') }}
                        </a>
                    </li>
                    <li class="flex items-start gap-3">
                        <flux:icon.phone class="mt-0.5 size-4 shrink-0 text-brand-500" />
                        <a href="tel:{{ str_replace(' ', '', \App\Models\Setting::get('contact_phone')) }}" class="transition hover:text-white">
                            {{ \App\Models\Setting::get('contact_phone') }}
                        </a>
                    </li>
                </ul>

                <div class="mt-6 flex gap-3">
                    <a href="{{ \App\Models\Setting::get('social_facebook') }}" target="_blank" rel="noopener" aria-label="Facebook"
                       class="rounded-full bg-zinc-800 p-2 transition hover:bg-brand-600 hover:text-white">
                        <x-site.social-icon name="facebook" class="size-4" />
                    </a>
                    <a href="{{ \App\Models\Setting::get('social_instagram') }}" target="_blank" rel="noopener" aria-label="Instagram"
                       class="rounded-full bg-zinc-800 p-2 transition hover:bg-brand-600 hover:text-white">
                        <x-site.social-icon name="instagram" class="size-4" />
                    </a>
                    <a href="{{ \App\Models\Setting::get('social_youtube') }}" target="_blank" rel="noopener" aria-label="YouTube"
                       class="rounded-full bg-zinc-800 p-2 transition hover:bg-brand-600 hover:text-white">
                        <x-site.social-icon name="youtube" class="size-4" />
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-zinc-800">
        <div class="mx-auto max-w-7xl px-6 py-6 text-center text-sm">
            &copy; {{ now()->year }} {{ config('site.name') }}. All Rights Reserved.
        </div>
    </div>
</footer>
