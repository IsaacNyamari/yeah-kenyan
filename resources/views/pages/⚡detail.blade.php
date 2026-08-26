<?php

use App\Models\Page;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.site')] class extends Component {
    public Page $page;

    public function mount(string $slug): void
    {
        $this->page = Page::query()->published()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Titles vary per page, so they are set at render time rather than
     * through the static #[Title] attribute.
     */
    public function rendering(View $view): void
    {
        $view->title($this->page->title);
    }
}; ?>

<div>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-zinc-900">
        <img src="{{ $page->image_url }}" alt="{{ $page->title }}"
             class="absolute inset-0 size-full object-cover opacity-40">
        <div class="relative mx-auto max-w-7xl px-6 py-24 sm:py-32">
            <span @class([
                'inline-block px-3 py-1 text-xs font-semibold tracking-wider uppercase text-white',
                'bg-brand-600' => $page->type === 'service',
                'bg-leaf-600' => $page->type === 'class',
            ])>
                {{ $page->type === 'service' ? 'Our Services' : 'Online Classes' }}
            </span>
            <h1 class="mt-4 max-w-3xl text-4xl font-bold tracking-tight text-white uppercase sm:text-5xl">
                {{ $page->title }}
            </h1>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-6 py-16">
        <div class="grid gap-12 lg:grid-cols-3">

            {{-- Main column --}}
            <div class="lg:col-span-2">
                <h2 class="text-3xl font-bold tracking-tight">{{ $page->heading }}</h2>

                <p class="mt-6 text-lg leading-relaxed text-zinc-600 dark:text-zinc-400">
                    {{ $page->intro }}
                </p>

                <flux:button wire:navigate href="{{ route('contact') }}" variant="primary" class="mt-8">
                    {{ $page->cta }}
                </flux:button>

                @foreach ($page->sections as $section)
                    <section class="mt-14">
                        <x-site.section-heading :title="$section['heading']" />

                        @isset($section['intro'])
                            <p class="mb-6 text-zinc-600 dark:text-zinc-400">{{ $section['intro'] }}</p>
                        @endisset

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($section['items'] as $item)
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-5 transition hover:border-brand-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                                    <h3 class="flex items-start gap-2 font-semibold">
                                        <flux:icon.check-circle class="mt-0.5 size-5 shrink-0 text-leaf-600" />
                                        {{ $item['label'] }}
                                    </h3>
                                    <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                        {{ $item['text'] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                @isset($page->footnotes)
                    <section class="mt-14">
                        <x-site.section-heading :title="$page->footnotes['heading']" />
                        @foreach ($page->footnotes['body'] as $paragraph)
                            <p class="mb-4 leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $paragraph }}</p>
                        @endforeach
                    </section>
                @endisset
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-8">
                <div class="rounded-lg bg-zinc-900 p-6 text-white">
                    <h3 class="text-lg font-bold uppercase">Request a Quote</h3>
                    <p class="mt-2 text-sm text-zinc-400">
                        Tell us about your event and we will get back to you with a tailored package.
                    </p>
                    <flux:button wire:navigate href="{{ route('contact') }}" variant="primary" class="mt-4 w-full">
                        Contact Us
                    </flux:button>

                    <dl class="mt-6 space-y-3 border-t border-zinc-800 pt-6 text-sm">
                        <div class="flex items-center gap-3">
                            <flux:icon.phone class="size-4 text-brand-500" />
                            <a href="tel:{{ str_replace(' ', '', \App\Models\Setting::get('contact_phone')) }}">{{ \App\Models\Setting::get('contact_phone') }}</a>
                        </div>
                        <div class="flex items-center gap-3">
                            <flux:icon.envelope class="size-4 text-brand-500" />
                            <a href="mailto:{{ \App\Models\Setting::get('contact_email') }}">{{ \App\Models\Setting::get('contact_email') }}</a>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
                    <h3 class="mb-4 font-bold tracking-wide uppercase">
                        {{ $page->type === 'service' ? 'Other Services' : 'Other Classes' }}
                    </h3>
                    <ul class="space-y-1 text-sm">
                        @foreach (\App\Models\Page::published()->where('type', $page->type)->whereKeyNot($page->id)->orderBy('sort_order')->get() as $other)
                            <li>
                                <a wire:navigate href="{{ route('page', $other->slug) }}"
                                   class="flex items-center gap-2 rounded px-2 py-1.5 transition hover:bg-zinc-100 hover:text-brand-600 dark:hover:bg-zinc-800">
                                    <flux:icon.chevron-right class="size-4 text-brand-600" />
                                    {{ $other->nav }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</div>
