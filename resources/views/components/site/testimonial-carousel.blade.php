@props(['testimonials'])

@php
    $items = collect($testimonials)->values();
    $total = $items->count();
@endphp

@if ($total > 0)
    <div
        x-data="{
            index: 0,
            total: {{ $total }},
            timer: null,

            next() { this.index = (this.index + 1) % this.total },
            prev() { this.index = (this.index - 1 + this.total) % this.total },
            go(i) { this.index = i },

            start() { this.stop(); if (this.total > 1) this.timer = setInterval(() => this.next(), 7000) },
            stop() { if (this.timer) clearInterval(this.timer) },
        }"
        x-init="start()"
        @mouseenter="stop()"
        @mouseleave="start()"
        @keydown.left.prevent="prev(); start()"
        @keydown.right.prevent="next(); start()"
        class="relative mx-auto max-w-3xl"
        role="region"
        aria-roledescription="carousel"
        aria-label="What our clients say"
        tabindex="0"
    >
        {{-- Viewport --}}
        <div class="overflow-hidden rounded-2xl">
            <div class="flex transition-transform duration-700 ease-[cubic-bezier(0.4,0,0.2,1)]"
                 :style="`transform: translateX(-${index * 100}%)`">

                @foreach ($items as $testimonial)
                    <figure class="w-full shrink-0">
                        <div class="flex h-full flex-col items-center border border-zinc-200 bg-white px-6 py-10 text-center text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100">

                            {{-- Image on top --}}
                            @if ($testimonial->image)
                                <img src="{{ $testimonial->image_url }}" alt="{{ $testimonial->client }}"
                                     loading="lazy"
                                     class="size-28 rounded-full object-cover ring-4 ring-zinc-100 dark:ring-zinc-800">
                            @else
                                <div class="flex size-28 items-center justify-center rounded-full bg-zinc-100 ring-4 ring-zinc-50 dark:bg-zinc-800 dark:ring-zinc-800/50">
                                    <span class="text-3xl font-black text-zinc-400">
                                        {{ str($testimonial->client)->substr(0, 1)->upper() }}
                                    </span>
                                </div>
                            @endif

                            {{-- Heading + rank --}}
                            <figcaption class="mt-6">
                                <p class="text-xl font-bold tracking-tight">{{ $testimonial->client }}</p>

                                @if ($testimonial->role)
                                    <p class="mt-1 text-sm font-semibold tracking-wide text-zinc-500 uppercase">
                                        {{ $testimonial->role }}
                                    </p>
                                @endif
                            </figcaption>

                            <flux:icon.chat-bubble-left-right class="mt-6 size-7 text-zinc-300 dark:text-zinc-700" />

                            {{-- Description --}}
                            <blockquote class="mt-3 max-w-xl text-lg leading-relaxed text-zinc-700 italic dark:text-zinc-300">
                                {{ $testimonial->quote }}
                            </blockquote>
                        </div>
                    </figure>
                @endforeach
            </div>
        </div>

        @if ($total > 1)
            <button type="button" @click="prev(); start()" aria-label="Previous testimonial"
                    class="absolute top-1/2 -left-3 z-10 hidden -translate-y-1/2 rounded-full border border-zinc-200 bg-white p-3 shadow-lg transition hover:bg-brand-600 hover:text-white sm:block lg:-left-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon.chevron-left class="size-5" />
            </button>

            <button type="button" @click="next(); start()" aria-label="Next testimonial"
                    class="absolute top-1/2 -right-3 z-10 hidden -translate-y-1/2 rounded-full border border-zinc-200 bg-white p-3 shadow-lg transition hover:bg-brand-600 hover:text-white sm:block lg:-right-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon.chevron-right class="size-5" />
            </button>

            <div class="mt-8 flex justify-center gap-2">
                @foreach ($items as $i => $testimonial)
                    <button type="button" @click="go({{ $i }}); start()"
                            aria-label="Show testimonial from {{ $testimonial->client }}"
                            :aria-current="index === {{ $i }}"
                            :class="index === {{ $i }} ? 'w-8 bg-brand-600' : 'w-2 bg-zinc-300 hover:bg-zinc-400 dark:bg-zinc-700'"
                            class="h-2 rounded-full transition-all"></button>
                @endforeach
            </div>
        @endif
    </div>
@endif
