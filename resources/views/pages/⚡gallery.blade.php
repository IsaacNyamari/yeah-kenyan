<?php

use App\Models\GalleryItem;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.site')] #[Title('Gallery')] class extends Component {
    #[Url(except: '')]
    public string $collection = '';

    public int $perPage = 24;

    /**
     * @return Collection<int, GalleryItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return GalleryItem::query()
            ->when($this->collection !== '', fn ($query) => $query->where('collection', $this->collection))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->take($this->perPage)
            ->get();
    }

    #[Computed]
    public function total(): int
    {
        return GalleryItem::query()
            ->when($this->collection !== '', fn ($query) => $query->where('collection', $this->collection))
            ->count();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function collections(): array
    {
        return GalleryItem::query()->distinct()->orderBy('collection')->pluck('collection')->all();
    }

    public function filterBy(string $collection): void
    {
        $this->collection = $collection;
        $this->perPage = 24;
    }

    public function loadMore(): void
    {
        $this->perPage += 24;
    }
}; ?>

<div>

    <section class="bg-zinc-900 py-20">
        <div class="mx-auto max-w-7xl px-6">
            <span class="inline-block bg-brand-600 px-3 py-1 text-xs font-semibold tracking-wider text-white uppercase">
                Our Work
            </span>
            <h1 class="mt-4 text-4xl font-bold tracking-tight text-white uppercase sm:text-5xl">Gallery</h1>
            <p class="mt-4 max-w-3xl text-zinc-400">{{ config('site.gallery_intro') }}</p>
        </div>
    </section>

    <div
        class="mx-auto max-w-7xl px-6 py-12"
        x-data="{ open: false, src: '', caption: '' }"
        @keydown.escape.window="open = false"
    >
        {{-- Filters --}}
        @if (count($this->collections) > 1)
            <div class="mb-8 flex flex-wrap gap-2">
                <button type="button" wire:click="filterBy('')"
                        @class([
                            'rounded-full px-4 py-2 text-sm font-medium transition',
                            'bg-brand-600 text-white' => $collection === '',
                            'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700' => $collection !== '',
                        ])>
                    All
                </button>

                @foreach ($this->collections as $name)
                    <button type="button" wire:click="filterBy('{{ $name }}')"
                            @class([
                                'rounded-full px-4 py-2 text-sm font-medium capitalize transition',
                                'bg-brand-600 text-white' => $collection === $name,
                                'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700' => $collection !== $name,
                            ])>
                        {{ $name }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Grid --}}
        <div wire:loading.class="opacity-50" class="columns-2 gap-4 transition sm:columns-3 lg:columns-4 [&>*]:mb-4">
            @forelse ($this->items as $item)
                <button type="button"
                        @click="open = true; src = '{{ $item->image_url }}'; caption = @js($item->title)"
                        class="group relative block w-full cursor-zoom-in overflow-hidden rounded-lg">
                    <img src="{{ $item->image_url }}" alt="{{ $item->title ?? 'Gallery image' }}" loading="lazy"
                         class="w-full transition duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-black/0 transition group-hover:bg-black/30"></div>
                </button>
            @empty
                <p class="text-zinc-500">No gallery images yet.</p>
            @endforelse
        </div>

        @if ($this->items->count() < $this->total)
            <div class="mt-10 text-center">
                <flux:button wire:click="loadMore" variant="primary">
                    <span wire:loading.remove wire:target="loadMore">Load More</span>
                    <span wire:loading wire:target="loadMore">Loading...</span>
                </flux:button>
            </div>
        @endif

        {{-- Lightbox --}}
        <div x-show="open" x-cloak x-transition.opacity @click="open = false"
             class="fixed inset-0 z-100 flex items-center justify-center bg-black/90 p-6">
            <button type="button" @click="open = false"
                    class="absolute top-6 right-6 text-white transition hover:text-brand-500" aria-label="Close">
                <flux:icon.x-mark class="size-8" />
            </button>
            <figure @click.stop class="max-h-full max-w-5xl">
                <img :src="src" :alt="caption" class="max-h-[80vh] w-auto rounded-lg object-contain">
                <figcaption x-show="caption" x-text="caption" class="mt-3 text-center text-sm text-zinc-300"></figcaption>
            </figure>
        </div>
    </div>
</div>
