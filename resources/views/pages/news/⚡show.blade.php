<?php

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.site')] class extends Component {
    public Post $post;

    public function mount(string $slug): void
    {
        $this->post = Post::query()
            ->published()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Titles vary per article, so they are set at render time rather than
     * through the static #[Title] attribute.
     */
    public function rendering(View $view): void
    {
        $view->title($this->post->title);
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function related(): Collection
    {
        return Post::query()
            ->published()
            ->with('category')
            ->where('category_id', $this->post->category_id)
            ->whereKeyNot($this->post->getKey())
            ->latest('published_at')
            ->take(3)
            ->get();
    }
}; ?>

<div>

    <article class="mx-auto max-w-4xl px-6 py-12">

        {{-- The category appears once, in the breadcrumb, and links to its filtered listing. --}}
        <nav class="mb-6 flex items-center gap-2 text-sm text-zinc-500">
            <a wire:navigate href="{{ route('news.index') }}" class="transition hover:text-brand-600">News</a>
            <flux:icon.chevron-right class="size-4" />
            <a wire:navigate href="{{ route('news.index', ['category' => $post->category->slug]) }}"
               class="capitalize transition hover:text-brand-600">
                {{ $post->category->name }}
            </a>
        </nav>

        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $post->title }}</h1>

        <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-zinc-500">
            <span class="flex items-center gap-1.5">
                <flux:icon.user class="size-4" /> {{ $post->author }}
            </span>
            <span class="flex items-center gap-1.5">
                <flux:icon.calendar class="size-4" />
                {{ $post->published_at?->siteTime()->format('M d, Y') }}
            </span>
        </div>

        @if ($post->image)
            <img src="{{ $post->image_url }}" alt="{{ $post->title }}"
                 class="mt-8 w-full rounded-lg object-cover">
        @endif

        @if ($post->excerpt)
            <p class="mt-8 border-l-4 border-brand-600 pl-4 text-lg leading-relaxed text-zinc-600 dark:text-zinc-400">
                {{ $post->excerpt }}
            </p>
        @endif

        {{--
            Bodies are stored as sanitized HTML (see App\Services\ArticleHtml),
            so they are rendered rather than escaped — escaping printed the tags
            as visible text. Styling is written out per element because the
            Tailwind typography plugin is not installed, which left the earlier
            "prose" classes doing nothing.
        --}}
        <div class="mt-8 text-base leading-relaxed text-zinc-700 dark:text-zinc-300
                    [&_blockquote]:my-6 [&_blockquote]:border-l-4 [&_blockquote]:border-brand-600 [&_blockquote]:pl-4 [&_blockquote]:text-zinc-600 [&_blockquote]:italic dark:[&_blockquote]:text-zinc-400
                    [&_h3]:mt-8 [&_h3]:mb-3 [&_h3]:text-2xl [&_h3]:font-bold [&_h3]:tracking-tight [&_h3]:text-zinc-900 dark:[&_h3]:text-zinc-100
                    [&_h4]:mt-8 [&_h4]:mb-3 [&_h4]:text-xl [&_h4]:font-bold [&_h4]:tracking-tight [&_h4]:text-zinc-900 dark:[&_h4]:text-zinc-100
                    [&_h5]:mt-6 [&_h5]:mb-2 [&_h5]:text-lg [&_h5]:font-semibold [&_h5]:text-zinc-900 dark:[&_h5]:text-zinc-100
                    [&_li]:mb-1 [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:ps-6 [&_p]:mb-5 [&_strong]:font-semibold [&_strong]:text-zinc-900 [&_ul]:my-4 [&_ul]:list-disc [&_ul]:ps-6 dark:[&_strong]:text-zinc-100">
            {!! $post->body !!}
        </div>

        {{-- Share --}}
        <div class="mt-12 flex items-center gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-800">
            <span class="text-sm font-semibold">Share:</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('news.show', $post->slug)) }}"
               target="_blank" rel="noopener" aria-label="Share on Facebook"
               class="rounded-full bg-zinc-100 p-2 transition hover:bg-brand-600 hover:text-white dark:bg-zinc-800">
                <x-site.social-icon name="facebook" class="size-4" />
            </a>
            <a href="https://wa.me/?text={{ urlencode($post->title.' '.route('news.show', $post->slug)) }}"
               target="_blank" rel="noopener" aria-label="Share on WhatsApp"
               class="rounded-full bg-zinc-100 p-2 transition hover:bg-leaf-600 hover:text-white dark:bg-zinc-800">
                <flux:icon.chat-bubble-left-right class="size-4" />
            </a>
        </div>
    </article>

    @if ($this->related->isNotEmpty())
        <section class="bg-zinc-50 py-16 dark:bg-zinc-900/40">
            <div class="mx-auto max-w-7xl px-6">
                <x-site.section-heading title="Related Stories" />
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->related as $related)
                        <x-site.post-card :post="$related" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
