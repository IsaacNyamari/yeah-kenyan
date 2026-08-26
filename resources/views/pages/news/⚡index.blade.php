<?php

use App\Models\Category;
use App\Models\Post;
use App\Services\NewYorkTimesFeed;
use App\Support\NewsItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.site')] #[Title('News')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $search = '';

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        return Post::query()
            ->published()
            ->with('category')
            ->when($this->category !== '', fn ($query) => $query->whereRelation('category', 'slug', $this->category))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('excerpt', 'like', "%{$this->search}%");
            }))
            ->latest('published_at')
            ->paginate(9);
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->withPublishedPosts()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function trending(): Collection
    {
        return Post::query()
            ->published()
            ->where('is_trending', true)
            ->latest('published_at')
            ->take(5)
            ->get();
    }

    /**
     * Live wire headlines.
     *
     * Held back while the visitor is filtering or searching: these are not our
     * articles, carry none of our categories, and are not in the database to
     * search against, so folding them into a filtered result would misrepresent
     * what matched.
     *
     * @return list<NewsItem>
     */
    #[Computed]
    public function wireHeadlines(): array
    {
        if ($this->category !== '' || $this->search !== '') {
            return [];
        }

        return app(NewYorkTimesFeed::class)->headlines();
    }

    public function filterBy(string $slug): void
    {
        $this->category = $slug;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div>

    <section class="bg-zinc-900 py-20">
        <div class="mx-auto max-w-7xl px-6">
            <span class="inline-block bg-brand-600 px-3 py-1 text-xs font-semibold tracking-wider text-white uppercase">
                Newsroom
            </span>
            <h1 class="mt-4 text-4xl font-bold tracking-tight text-white uppercase sm:text-5xl">News &amp; Updates</h1>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-6 py-12">
        <div class="grid gap-12 lg:grid-cols-4">

            <div class="lg:col-span-3">
                {{-- Search + filters --}}
                <div class="mb-8 space-y-4">
                    <flux:input
                        wire:model.live.debounce.400ms="search"
                        icon="magnifying-glass"
                        placeholder="Search news..."
                        aria-label="Search news"
                    />

                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="filterBy('')"
                                @class([
                                    'rounded-full px-4 py-1.5 text-sm font-medium transition',
                                    'bg-brand-600 text-white' => $category === '',
                                    'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700' => $category !== '',
                                ])>
                            All
                        </button>

                        @foreach ($this->categories as $item)
                            <button type="button" wire:click="filterBy('{{ $item->slug }}')"
                                    @class([
                                        'rounded-full px-4 py-1.5 text-sm font-medium capitalize transition',
                                        'bg-brand-600 text-white' => $category === $item->slug,
                                        'bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700' => $category !== $item->slug,
                                    ])>
                                {{ $item->name }}
                                <span class="ml-1 text-xs opacity-70">{{ $item->posts_count }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Results --}}
                <div wire:loading.class="opacity-50" class="grid gap-6 transition sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($this->posts as $post)
                        <x-site.post-card :post="$post" />
                    @empty
                        <div class="col-span-full rounded-lg border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
                            <flux:icon.newspaper class="mx-auto size-10 text-zinc-400" />
                            <p class="mt-3 text-zinc-500">No articles match your search.</p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-10">
                    {{ $this->posts->links() }}
                </div>

                {{-- Live headlines, clearly separated from our own reporting. --}}
                @if ($this->wireHeadlines !== [])
                    <section class="mt-16">
                        <div class="mb-6 flex flex-wrap items-end justify-between gap-3 border-b-2 border-zinc-200 dark:border-zinc-800">
                            <h2 class="-mb-0.5 inline-block border-b-2 border-brand-600 pb-2 text-lg font-bold tracking-wide uppercase">
                                From the wire
                            </h2>
                            <p class="pb-2 text-xs text-zinc-500">
                                Headlines from The New York Times &middot; opens on nytimes.com
                            </p>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($this->wireHeadlines as $headline)
                                <x-site.post-card :item="$headline" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-8">
                @if ($this->trending->isNotEmpty())
                    <div>
                        <x-site.section-heading title="Trending" />
                        <ul class="space-y-4">
                            @foreach ($this->trending as $post)
                                <li>
                                    <a wire:navigate href="{{ route('news.show', $post->slug) }}" class="group flex gap-3">
                                        @if ($post->image)
                                            <img src="{{ $post->image_url }}" alt="{{ $post->title }}" loading="lazy"
                                                 class="size-16 shrink-0 rounded object-cover">
                                        @endif
                                        <div>
                                            <h3 class="line-clamp-2 text-sm font-semibold transition group-hover:text-brand-600">
                                                {{ $post->title }}
                                            </h3>
                                            <p class="mt-1 text-xs text-zinc-500">{{ site_time($post->published_at)?->diffForHumans() }}</p>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <x-site.section-heading title="Tags" />
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->categories as $item)
                            <button type="button" wire:click="filterBy('{{ $item->slug }}')"
                                    class="rounded bg-zinc-100 px-3 py-1 text-xs capitalize transition hover:bg-brand-600 hover:text-white dark:bg-zinc-800">
                                {{ $item->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg bg-zinc-900 p-6 text-white">
                    <h3 class="font-bold uppercase">Need Event Coverage?</h3>
                    <p class="mt-2 text-sm text-zinc-400">
                        We document, stream and produce events across Kenya.
                    </p>
                    <flux:button wire:navigate href="{{ route('contact') }}" variant="primary" class="mt-4 w-full">
                        Talk To Us
                    </flux:button>
                </div>
            </aside>
        </div>
    </div>
</div>
