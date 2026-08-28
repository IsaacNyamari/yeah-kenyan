<?php

use App\Models\HeroPanel;
use App\Models\Post;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\HeroPanelKind;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.site')] class extends Component {
    /**
     * @return Collection<int, HeroPanel>
     */
    #[Computed]
    public function heroSlides(): Collection
    {
        return HeroPanel::visible()->ofKind(HeroPanelKind::Slide)->get();
    }

    /**
     * @return Collection<int, HeroPanel>
     */
    #[Computed]
    public function heroTiles(): Collection
    {
        return HeroPanel::visible()->ofKind(HeroPanelKind::Tile)->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function featured(): Collection
    {
        return Post::query()
            ->published()
            ->with('category')
            ->where('is_featured', true)
            ->latest('published_at')
            ->take(8)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function latest(): Collection
    {
        return Post::query()
            ->published()
            ->with('category')
            ->latest('published_at')
            ->take(6)
            ->get();
    }

    /**
     * @return Collection<int, TeamMember>
     */
    #[Computed]
    public function team(): Collection
    {
        return TeamMember::orderBy('sort_order')->get();
    }

    /**
     * @return Collection<int, Testimonial>
     */
    #[Computed]
    public function testimonials(): Collection
    {
        return Testimonial::orderBy('sort_order')->get();
    }
}; ?>

<div>

    {{-- Hero --}}
    @if ($this->heroSlides->isNotEmpty() || $this->heroTiles->isNotEmpty())
        <section class="mx-auto max-w-[1600px] px-4 pt-6">
            <div class="grid gap-3 lg:grid-cols-12">

                {{-- Rotating feature panel --}}
                @if ($this->heroSlides->isNotEmpty())
                    <div
                        x-data="{
                            active: 0,
                            total: {{ $this->heroSlides->count() }},
                            timer: null,
                            start() { if (this.total > 1) { this.timer = setInterval(() => this.active = (this.active + 1) % this.total, 6000) } },
                            stop() { clearInterval(this.timer) },
                        }"
                        x-init="start()"
                        @mouseenter="stop()" @mouseleave="start()"
                        @class([
                            'relative h-[420px] overflow-hidden rounded-lg lg:h-[500px]',
                            'lg:col-span-7' => $this->heroTiles->isNotEmpty(),
                            'lg:col-span-12' => $this->heroTiles->isEmpty(),
                        ])
                    >
                        @foreach ($this->heroSlides as $index => $slide)
                            <div x-show="active === {{ $index }}" x-transition.opacity.duration.700ms class="absolute inset-0">
                                <img src="{{ $slide->image_url }}" alt="{{ $slide->badge }}" class="size-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent"></div>
                                <div class="absolute bottom-0 p-8">
                                    <span class="inline-block bg-brand-600 px-3 py-1 text-xs font-semibold tracking-wider text-white uppercase">
                                        {{ $slide->badge }}
                                    </span>
                                    <p class="mt-3 max-w-2xl text-2xl font-bold text-white uppercase sm:text-3xl">
                                        {{ $slide->text }}
                                    </p>
                                </div>
                            </div>
                        @endforeach

                        @if ($this->heroSlides->count() > 1)
                            <div class="absolute right-6 bottom-6 flex gap-2">
                                @foreach ($this->heroSlides as $index => $slide)
                                    <button type="button" @click="active = {{ $index }}"
                                            :class="active === {{ $index }} ? 'bg-brand-500 w-8' : 'bg-white/50 w-2'"
                                            class="h-2 rounded-full transition-all"
                                            aria-label="Go to slide {{ $index + 1 }}"></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Static tiles --}}
                @if ($this->heroTiles->isNotEmpty())
                    <div @class([
                        'grid gap-3 sm:grid-cols-2',
                        'lg:col-span-5' => $this->heroSlides->isNotEmpty(),
                        'lg:col-span-12' => $this->heroSlides->isEmpty(),
                    ])>
                        @foreach ($this->heroTiles as $tile)
                            <div class="group relative h-[240px] overflow-hidden rounded-lg">
                                <img src="{{ $tile->image_url }}" alt="{{ $tile->badge }}"
                                     class="size-full object-cover transition duration-500 group-hover:scale-105">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent"></div>
                                <div class="absolute bottom-0 p-4">
                                    <span class="inline-block bg-leaf-600 px-2 py-0.5 text-[10px] font-semibold tracking-wider text-white uppercase">
                                        {{ $tile->badge }}
                                    </span>
                                    <p class="mt-2 text-sm font-semibold text-white uppercase">{{ $tile->text }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Services --}}
    <section class="mx-auto max-w-7xl px-6 py-16">
        <x-site.section-heading title="What We Do" />

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (\App\Models\Page::navigation(\App\Models\Page::TYPE_SERVICE)->take(6) as $service)
                <a wire:navigate href="{{ route('page', $service->slug) }}"
                   class="group overflow-hidden rounded-lg border border-zinc-200 transition hover:-translate-y-1 hover:shadow-xl dark:border-zinc-800">
                    <div class="relative h-48 overflow-hidden">
                        <img src="{{ $service->image_url }}" alt="{{ $service->nav }}"
                             class="size-full object-cover transition duration-500 group-hover:scale-110">
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold transition group-hover:text-brand-600">{{ $service->nav }}</h3>
                        <p class="mt-2 line-clamp-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $service->intro }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-600">
                            Learn more <flux:icon.arrow-right class="size-4" />
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Featured news --}}
    @if ($this->featured->isNotEmpty())
        <section class="bg-zinc-50 py-16 dark:bg-zinc-900/40">
            <div class="mx-auto max-w-7xl px-6">
                <x-site.section-heading title="Featured News" />

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($this->featured as $post)
                        <x-site.post-card :post="$post" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Latest news --}}
    @if ($this->latest->isNotEmpty())
        <section class="mx-auto max-w-7xl px-6 py-16">
            <x-site.section-heading title="Latest News" />

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->latest as $post)
                    <x-site.post-card :post="$post" />
                @endforeach
            </div>

            <div class="mt-8 text-center">
                <flux:button wire:navigate href="{{ route('news.index') }}" variant="primary">
                    View All News
                </flux:button>
            </div>
        </section>
    @endif

    {{-- Team --}}
    @if ($this->team->isNotEmpty())
        <section class="bg-zinc-50 py-16 dark:bg-zinc-900/40">
            <div class="mx-auto max-w-7xl px-6">
                <x-site.section-heading title="Our Team" />
                <p class="mb-10 max-w-3xl text-zinc-600 dark:text-zinc-400">{{ config('site.team_intro') }}</p>

                <x-site.team-carousel :members="$this->team" />
            </div>
        </section>
    @endif

    {{-- Testimonials --}}
    @if ($this->testimonials->isNotEmpty())
        <section class="mx-auto max-w-7xl px-6 py-16">
            <x-site.section-heading title="What Our Clients Say" />

            <x-site.testimonial-carousel :testimonials="$this->testimonials" />
        </section>
    @endif

    {{-- Call to action --}}
    <section class="bg-zinc-900">
        <div class="mx-auto max-w-7xl px-6 py-16 text-center">
            <h2 class="text-3xl font-bold text-white uppercase">{{ config('site.slogan') }}</h2>
            <p class="mx-auto mt-4 max-w-2xl text-zinc-400">
                Tell us about your event and we will bring the technology, the crew and the creativity.
            </p>
            <flux:button wire:navigate href="{{ route('contact') }}" variant="primary" class="mt-8">
                Get In Touch
            </flux:button>
        </div>
    </section>
</div>
