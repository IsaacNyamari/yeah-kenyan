@props(['members'])

@php
    $items = collect($members)->values();
    $total = $items->count();
@endphp

@if ($total > 0)
    <div
        x-data="{
            index: 0,
            total: {{ $total }},
            timer: null,

            /*
             * Offset of a card from the focused one, wrapped so the track has
             * no start or end — card 0 sits next to the last card.
             */
            offset(i) {
                let d = i - this.index;
                const half = Math.floor(this.total / 2);

                if (d > half) d -= this.total;
                if (d < -half) d += this.total;

                return d;
            },

            next() { this.index = (this.index + 1) % this.total },
            prev() { this.index = (this.index - 1 + this.total) % this.total },
            go(i) { this.index = i },

            start() { this.stop(); if (this.total > 1) this.timer = setInterval(() => this.next(), 4500) },
            stop() { if (this.timer) clearInterval(this.timer) },
        }"
        x-init="start()"
        @mouseenter="stop()"
        @mouseleave="start()"
        @keydown.left.prevent="prev(); start()"
        @keydown.right.prevent="next(); start()"
        class="relative"
        role="region"
        aria-roledescription="carousel"
        aria-label="Our team"
        tabindex="0"
    >
        {{-- Stage. Cards are absolutely positioned and moved relative to centre. --}}
        <div class="relative mx-auto h-[520px] max-w-5xl overflow-hidden">
            @foreach ($items as $i => $member)
                <article
                    x-data="{ d: 0 }"
                    x-effect="d = offset({{ $i }})"
                    :style="`
                        transform: translateX(calc(-50% + ${d * 62}%)) scale(${d === 0 ? 1 : 0.86});
                        opacity: ${Math.abs(d) > 1 ? 0 : (d === 0 ? 1 : 0.45)};
                        z-index: ${30 - Math.abs(d)};
                        filter: ${d === 0 ? 'none' : 'saturate(0.4)'};
                    `"
                    :aria-hidden="d !== 0"
                    :inert="d !== 0 ? true : null"
                    class="absolute top-0 left-1/2 w-[300px] overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl transition-all duration-700 ease-[cubic-bezier(0.4,0,0.2,1)] sm:w-[340px] dark:border-zinc-800 dark:bg-zinc-900"
                >
                    {{--
                        Photo with the role laid over it.

                        Team photos range from tall portraits (0.67) to landscape
                        (1.50), so object-contain shows each one whole rather than
                        cropping heads out of frame. The dark panel behind makes
                        the letterboxing read as deliberate and keeps the white
                        role text legible whatever the image does.
                    --}}
                    <div class="relative h-64 overflow-hidden bg-zinc-900">
                        <img src="{{ asset($member->photo) }}" alt="{{ $member->name }}" loading="lazy"
                             class="size-full object-contain">

                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/55 to-transparent px-5 pt-10 pb-4">
                            <p class="text-xs font-bold tracking-[0.15em] text-white uppercase">
                                {{ $member->role }}
                            </p>
                        </div>
                    </div>

                    {{-- Name, then bio --}}
                    <div class="px-6 py-5">
                        <h3 class="text-xl leading-snug font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                            {{ $member->name }}
                        </h3>

                        <p class="mt-3 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            {{ $member->bio }}
                        </p>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($total > 1)
            <button type="button" @click="prev(); start()" aria-label="Previous team member"
                    class="absolute top-1/2 left-0 z-40 hidden -translate-y-1/2 rounded-full border border-zinc-200 bg-white p-3 shadow-lg transition hover:bg-zinc-900 hover:text-white sm:block lg:left-4 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-white dark:hover:text-zinc-900">
                <flux:icon.chevron-left class="size-5" />
            </button>

            <button type="button" @click="next(); start()" aria-label="Next team member"
                    class="absolute top-1/2 right-0 z-40 hidden -translate-y-1/2 rounded-full border border-zinc-200 bg-white p-3 shadow-lg transition hover:bg-zinc-900 hover:text-white sm:block lg:right-4 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-white dark:hover:text-zinc-900">
                <flux:icon.chevron-right class="size-5" />
            </button>

            <div class="mt-6 flex justify-center gap-2">
                @foreach ($items as $i => $member)
                    <button type="button" @click="go({{ $i }}); start()"
                            aria-label="Show {{ $member->name }}"
                            :aria-current="index === {{ $i }}"
                            :class="index === {{ $i }} ? 'w-8 bg-zinc-900 dark:bg-white' : 'w-2 bg-zinc-300 hover:bg-zinc-400 dark:bg-zinc-700'"
                            class="h-2 rounded-full transition-all"></button>
                @endforeach
            </div>
        @endif
    </div>
@endif
