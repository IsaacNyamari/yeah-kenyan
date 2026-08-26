@props(['post' => null, 'item' => null])

@php
    // Accepts either one of our Post models or an already-mapped NewsItem,
    // so the listing can mix our articles with live wire headlines.
    $news = $item ?? \App\Support\NewsItem::fromPost($post);
@endphp

<article class="group overflow-hidden rounded-lg border border-zinc-200 bg-white transition hover:-translate-y-1 hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
    <a href="{{ $news->url }}" @foreach ($news->linkAttributes() as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach class="block">
        <div class="relative h-48 overflow-hidden bg-zinc-100 dark:bg-zinc-800">
            @if ($news->imageUrl)
                <img src="{{ $news->imageUrl }}" alt="{{ $news->title }}" loading="lazy"
                     class="size-full object-cover transition duration-500 group-hover:scale-110">
            @endif

            <span class="absolute top-3 left-3 bg-brand-600 px-2 py-1 text-[10px] font-semibold tracking-wider text-white uppercase">
                {{ $news->categoryName }}
            </span>

            @if ($news->isExternal)
                {{-- Named so nobody mistakes a wire headline for our own reporting. --}}
                <span class="absolute top-3 right-3 flex items-center gap-1 rounded bg-black/75 px-2 py-1 text-[10px] font-medium text-white backdrop-blur-sm">
                    <flux:icon.arrow-top-right-on-square class="size-3" />
                    {{ $news->source }}
                </span>
            @endif
        </div>

        <div class="p-5">
            <h3 class="line-clamp-2 font-bold transition group-hover:text-brand-600">{{ $news->title }}</h3>

            @if ($news->excerpt)
                <p class="mt-2 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $news->excerpt }}</p>
            @endif

            <div class="mt-4 flex items-center gap-3 text-xs text-zinc-500">
                @if ($news->author)
                    <span class="flex items-center gap-1 truncate">
                        <flux:icon.user class="size-3.5 shrink-0" /> {{ $news->author }}
                    </span>
                @endif

                <span class="flex shrink-0 items-center gap-1">
                    <flux:icon.calendar class="size-3.5" />
                    {{ site_time($news->publishedAt)?->diffForHumans() }}
                </span>
            </div>
        </div>
    </a>
</article>
