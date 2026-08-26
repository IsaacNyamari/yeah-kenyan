<?php

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.site')] #[Title('About Us')] class extends Component {
    /**
     * @return Collection<int, TeamMember>
     */
    #[Computed]
    public function team(): Collection
    {
        return TeamMember::orderBy('sort_order')->get();
    }
}; ?>

<div>

    <section class="relative overflow-hidden bg-zinc-900">
        <img src="{{ asset('images/team1.jpg') }}" alt="{{ config('site.name') }}"
             class="absolute inset-0 size-full object-cover opacity-30">
        <div class="relative mx-auto max-w-7xl px-6 py-24 sm:py-32">
            <span class="inline-block bg-brand-600 px-3 py-1 text-xs font-semibold tracking-wider text-white uppercase">
                About Us
            </span>
            <h1 class="mt-4 text-4xl font-bold tracking-tight text-white uppercase sm:text-5xl">
                {{ config('site.name') }}
            </h1>
            <p class="mt-4 text-xl text-leaf-400">{{ config('site.about.tagline') }}</p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-6 py-16">
        <div class="grid gap-12 lg:grid-cols-3">

            <div class="lg:col-span-2">
                <x-site.section-heading title="Our Story" />

                @foreach (config('site.about.body') as $paragraph)
                    <p class="mb-5 leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $paragraph }}</p>
                @endforeach

                <p class="mt-8 mb-4 font-semibold">{{ config('site.about.offering_intro') }}</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach (config('site.about.offerings') as $offering)
                        <div class="flex items-start gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                            <flux:icon.sparkles class="mt-0.5 size-5 shrink-0 text-leaf-600" />
                            <div>
                                <h3 class="text-sm font-bold">{{ $offering['label'] }}</h3>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $offering['text'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                @foreach (config('site.about.closing') as $paragraph)
                    <p class="mt-5 leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $paragraph }}</p>
                @endforeach
            </div>

            <aside class="space-y-6">
                <div class="rounded-lg bg-brand-600 p-6 text-center text-white">
                    <flux:icon.megaphone class="mx-auto size-8" />
                    <h3 class="mt-3 text-sm font-semibold tracking-wider uppercase">Our Slogan</h3>
                    <p class="mt-2 text-2xl font-bold">{{ config('site.slogan') }}</p>
                </div>

                <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
                    <flux:icon.eye class="size-8 text-leaf-600" />
                    <h3 class="mt-3 font-bold tracking-wide uppercase">Our Vision</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        {{ config('site.about.vision') }}
                    </p>
                </div>

                <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
                    <flux:icon.flag class="size-8 text-brand-600" />
                    <h3 class="mt-3 font-bold tracking-wide uppercase">Our Mission</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                        {{ config('site.about.mission') }}
                    </p>
                </div>

                <flux:button wire:navigate href="{{ route('contact') }}" variant="primary" class="w-full">
                    Work With Us
                </flux:button>
            </aside>
        </div>

        {{-- Team --}}
        @if ($this->team->isNotEmpty())
            <section class="mt-20">
                <x-site.section-heading title="Our Team" />
                <p class="mb-10 max-w-3xl text-zinc-600 dark:text-zinc-400">{{ config('site.team_intro') }}</p>

                <x-site.team-carousel :members="$this->team" />
            </section>
        @endif
    </div>
</div>
