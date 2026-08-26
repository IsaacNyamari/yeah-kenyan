<?php

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\GalleryItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Dashboard')] class extends Component {
    /**
     * Headline counters for the stat tiles.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'posts' => Post::count(),
            'published' => Post::published()->count(),
            'drafts' => Post::whereNull('published_at')->count(),
            'gallery' => GalleryItem::count(),
            'services' => Page::services()->count(),
            'classes' => Page::classes()->count(),
            'messages' => ContactMessage::count(),
            'unread' => ContactMessage::whereNull('read_at')->count(),
            'subscribers' => Subscriber::count(),
        ];
    }

    /**
     * Articles and enquiries per month for the last twelve months.
     *
     * @return array<int, array{label: string, posts: int, messages: int}>
     */
    #[Computed]
    public function monthlyActivity(): array
    {
        $start = now()->startOfMonth()->subMonths(11);

        $posts = Post::query()
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $start)
            ->get()
            ->countBy(fn (Post $post): string => site_time($post->published_at)?->format('Y-m'));

        $messages = ContactMessage::query()
            ->where('created_at', '>=', $start)
            ->get()
            ->countBy(fn (ContactMessage $message): string => site_time($message->created_at)?->format('Y-m'));

        $months = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');

            $months[] = [
                'label' => $month->format('M'),
                'posts' => $posts[$key] ?? 0,
                'messages' => $messages[$key] ?? 0,
            ];
        }

        return $months;
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categoryBreakdown(): Collection
    {
        return Category::query()
            ->withCount('posts')
            ->whereHas('posts')
            ->orderByDesc('posts_count')
            ->take(6)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function recentPosts(): Collection
    {
        return Post::with('category')->latest()->take(5)->get();
    }

    /**
     * @return Collection<int, ContactMessage>
     */
    #[Computed]
    public function recentMessages(): Collection
    {
        return ContactMessage::latest()->take(5)->get();
    }

    /**
     * Total bytes held in the public image directories.
     */
    #[Computed]
    public function imageFootprint(): string
    {
        $bytes = 0;

        foreach (['uploads', 'images'] as $directory) {
            foreach (glob(public_path("$directory/*")) ?: [] as $file) {
                if (is_file($file)) {
                    $bytes += filesize($file) ?: 0;
                }
            }
        }

        return $bytes > 1_073_741_824
            ? round($bytes / 1_073_741_824, 1).' GB'
            : round($bytes / 1_048_576).' MB';
    }
}; ?>

<div class="space-y-6">

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Dashboard</flux:heading>
            <flux:text class="mt-1">An overview of your content, enquiries and audience.</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('admin.posts')" wire:navigate>
            New article
        </flux:button>
    </div>

    {{-- Stat tiles --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-tile
            label="Articles"
            :value="$this->stats['posts']"
            :meta="$this->stats['published'].' live · '.$this->stats['drafts'].' draft'"
            icon="newspaper"
            tone="brand"
            :href="route('admin.posts')" />

        <x-admin.stat-tile
            label="Gallery images"
            :value="$this->stats['gallery']"
            :meta="$this->imageFootprint.' on disk'"
            icon="photo"
            tone="leaf"
            :href="route('admin.gallery')" />

        <x-admin.stat-tile
            label="Messages"
            :value="$this->stats['messages']"
            :meta="$this->stats['unread'].' unread'"
            icon="inbox"
            tone="amber"
            :href="route('admin.messages')" />

        <x-admin.stat-tile
            label="Subscribers"
            :value="$this->stats['subscribers']"
            meta="Newsletter sign-ups"
            icon="users"
            tone="sky" />
    </div>

    {{-- Activity chart --}}
    <flux:card>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="lg">Activity</flux:heading>
                <flux:text size="sm" class="mt-0.5">Articles published and enquiries received, last 12 months</flux:text>
            </div>

            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-sm bg-brand-500"></span> Articles
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-sm bg-leaf-500"></span> Messages
                </span>
            </div>
        </div>

        @php
            $activity = $this->monthlyActivity;
            $peak = max(1, max(array_map(fn ($m) => max($m['posts'], $m['messages']), $activity)));
        @endphp

        <div class="mt-6 flex h-48 items-end gap-2">
            @foreach ($activity as $month)
                <div class="group flex flex-1 flex-col items-center gap-1.5">
                    <div class="flex w-full flex-1 items-end justify-center gap-0.5">
                        <div class="w-1/2 rounded-t bg-brand-500 transition-all group-hover:bg-brand-600"
                             style="height: {{ max(2, round($month['posts'] / $peak * 100)) }}%"
                             title="{{ $month['posts'] }} article(s)"></div>
                        <div class="w-1/2 rounded-t bg-leaf-500 transition-all group-hover:bg-leaf-600"
                             style="height: {{ max(2, round($month['messages'] / $peak * 100)) }}%"
                             title="{{ $month['messages'] }} message(s)"></div>
                    </div>
                    <span class="text-[10px] text-zinc-500">{{ $month['label'] }}</span>
                </div>
            @endforeach
        </div>
    </flux:card>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Category breakdown --}}
        <flux:card>
            <flux:heading size="lg">Top categories</flux:heading>

            @php $topCount = max(1, (int) $this->categoryBreakdown->max('posts_count')); @endphp

            <div class="mt-5 space-y-3">
                @forelse ($this->categoryBreakdown as $category)
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="capitalize">{{ $category->name }}</span>
                            <span class="text-zinc-500">{{ $category->posts_count }}</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-brand-500"
                                 style="width: {{ round($category->posts_count / $topCount * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <flux:text size="sm">No articles published yet.</flux:text>
                @endforelse
            </div>
        </flux:card>

        {{-- Recent articles --}}
        <flux:card>
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Recent articles</flux:heading>
                <flux:link :href="route('admin.posts')" wire:navigate class="text-sm">View all</flux:link>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($this->recentPosts as $post)
                    <div class="flex items-center gap-3">
                        @if ($post->image)
                            <img src="{{ $post->image_url }}" alt="" class="size-10 shrink-0 rounded object-cover">
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $post->title }}</p>
                            <p class="text-xs text-zinc-500">
                                <span class="capitalize">{{ $post->category->name }}</span> ·
                                {{ site_time($post->published_at)?->diffForHumans(short: true) ?? 'Draft' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <flux:text size="sm">No articles yet.</flux:text>
                @endforelse
            </div>
        </flux:card>

        {{-- Recent messages --}}
        <flux:card>
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Recent enquiries</flux:heading>
                <flux:link :href="route('admin.messages')" wire:navigate class="text-sm">Inbox</flux:link>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($this->recentMessages as $message)
                    <div class="flex items-start gap-2">
                        @if ($message->read_at === null)
                            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-600"></span>
                        @else
                            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-zinc-300 dark:bg-zinc-700"></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $message->subject }}</p>
                            <p class="truncate text-xs text-zinc-500">
                                {{ $message->name }} · {{ site_time($message->created_at)?->diffForHumans(short: true) }}
                            </p>
                        </div>
                    </div>
                @empty
                    <flux:text size="sm">No enquiries yet.</flux:text>
                @endforelse
            </div>
        </flux:card>
    </div>

    {{-- Content shortcuts --}}
    <flux:card>
        <flux:heading size="lg">Site content</flux:heading>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('admin.services') }}" wire:navigate
               class="rounded-lg border border-zinc-200 p-4 transition hover:border-brand-400 dark:border-zinc-700">
                <flux:icon.briefcase class="size-5 text-brand-600" />
                <p class="mt-2 text-2xl font-bold">{{ $this->stats['services'] }}</p>
                <p class="text-sm text-zinc-500">Service pages</p>
            </a>

            <a href="{{ route('admin.classes') }}" wire:navigate
               class="rounded-lg border border-zinc-200 p-4 transition hover:border-leaf-400 dark:border-zinc-700">
                <flux:icon.academic-cap class="size-5 text-leaf-600" />
                <p class="mt-2 text-2xl font-bold">{{ $this->stats['classes'] }}</p>
                <p class="text-sm text-zinc-500">Online classes</p>
            </a>

            <a href="{{ route('admin.contact') }}" wire:navigate
               class="rounded-lg border border-zinc-200 p-4 transition hover:border-brand-400 dark:border-zinc-700">
                <flux:icon.cog-6-tooth class="size-5 text-zinc-500" />
                <p class="mt-2 text-sm font-semibold">Contact settings</p>
                <p class="text-sm text-zinc-500">Copy &amp; details</p>
            </a>

            <a href="{{ route('home') }}" target="_blank"
               class="rounded-lg border border-zinc-200 p-4 transition hover:border-brand-400 dark:border-zinc-700">
                <flux:icon.globe-alt class="size-5 text-zinc-500" />
                <p class="mt-2 text-sm font-semibold">View website</p>
                <p class="text-sm text-zinc-500">Opens in a new tab</p>
            </a>
        </div>
    </flux:card>
</div>
