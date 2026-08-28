<?php

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\GalleryItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Subscriber;
use App\Support\PostStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The dashboard shows each person their own work first, and only those
 * site-wide panels they hold the permission for. An author has no business
 * seeing the enquiry inbox, and a moderator none seeing gallery figures.
 */
new #[Layout('layouts.app')] #[Title('Dashboard')] class extends Component {
    /**
     * The signed-in account's own articles, counted by where they sit.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function myArticles(): array
    {
        $mine = Post::where('submitted_by', auth()->id());

        return [
            'total' => (clone $mine)->count(),
            'pending' => (clone $mine)->where('status', PostStatus::Pending)->count(),
            'approved' => (clone $mine)->where('status', PostStatus::Approved)->count(),
            'rejected' => (clone $mine)->where('status', PostStatus::Rejected)->count(),
            'live' => (clone $mine)->published()->count(),
        ];
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function myRecentPosts(): Collection
    {
        return Post::with('category')
            ->where('submitted_by', auth()->id())
            ->latest('updated_at')
            ->take(5)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    #[Computed]
    public function reviewQueue(): Collection
    {
        return Post::with('category', 'submitter')
            ->awaitingReview()
            ->latest('updated_at')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function awaitingCount(): int
    {
        return Post::awaitingReview()->count();
    }

    /**
     * Headline counters, each read only where the viewer may see it.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'posts' => Gate::allows('moderate posts') ? Post::count() : 0,
            'published' => Gate::allows('moderate posts') ? Post::published()->count() : 0,
            'gallery' => Gate::allows('manage gallery') ? GalleryItem::count() : 0,
            'services' => Gate::allows('manage services') ? Page::services()->count() : 0,
            'classes' => Gate::allows('manage classes') ? Page::classes()->count() : 0,
            'messages' => Gate::allows('manage messages') ? ContactMessage::count() : 0,
            'unread' => Gate::allows('manage messages') ? ContactMessage::whereNull('read_at')->count() : 0,
            'subscribers' => Gate::allows('manage subscribers') ? Subscriber::subscribed()->count() : 0,
        ];
    }

    /**
     * Articles and enquiries per month for the last twelve months. Each series
     * is filled only for a viewer entitled to it, so the chart never leaks a
     * figure the tiles are hiding.
     *
     * @return array<int, array{label: string, posts: int, messages: int}>
     */
    #[Computed]
    public function monthlyActivity(): array
    {
        $start = now()->startOfMonth()->subMonths(11);

        $posts = Gate::allows('moderate posts')
            ? Post::query()
                ->whereNotNull('published_at')
                ->where('published_at', '>=', $start)
                ->get()
                ->countBy(fn (Post $post): string => site_time($post->published_at)?->format('Y-m'))
            : collect();

        $messages = Gate::allows('manage messages')
            ? ContactMessage::query()
                ->where('created_at', '>=', $start)
                ->get()
                ->countBy(fn (ContactMessage $message): string => site_time($message->created_at)?->format('Y-m'))
            : collect();

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
            <flux:heading size="xl">Welcome back, {{ str(auth()->user()->name)->before(' ') }}</flux:heading>
            <flux:text class="mt-1">
                @if (auth()->user()->primaryRole())
                    You are signed in as {{ auth()->user()->primaryRole()->label() }}.
                @endif
            </flux:text>
        </div>

        @can('manage news')
            <flux:button variant="primary" icon="plus" :href="route('admin.posts')" wire:navigate>
                New article
            </flux:button>
        @endcan
    </div>

    {{-- Your own work --}}
    @can('manage news')
        <flux:card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:heading size="lg">Your articles</flux:heading>
                <flux:link :href="route('admin.posts')" wire:navigate class="text-sm">Open the editor</flux:link>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-4">
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <p class="text-2xl font-bold">{{ $this->myArticles['live'] }}</p>
                    <p class="text-sm text-zinc-500">Live on the site</p>
                </div>
                <div class="rounded-lg border border-amber-300 p-4 dark:border-amber-700">
                    <p class="text-2xl font-bold">{{ $this->myArticles['pending'] }}</p>
                    <p class="text-sm text-zinc-500">Awaiting review</p>
                </div>
                <div class="rounded-lg border border-red-300 p-4 dark:border-red-800">
                    <p class="text-2xl font-bold">{{ $this->myArticles['rejected'] }}</p>
                    <p class="text-sm text-zinc-500">Sent back to you</p>
                </div>
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <p class="text-2xl font-bold">{{ $this->myArticles['total'] }}</p>
                    <p class="text-sm text-zinc-500">Written in total</p>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($this->myRecentPosts as $post)
                    <div class="flex items-center gap-3">
                        @if ($post->image)
                            <img src="{{ $post->image_url }}" alt="" class="size-10 shrink-0 rounded object-cover">
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $post->title }}</p>
                            <p class="text-xs text-zinc-500">
                                <span class="capitalize">{{ $post->category->name }}</span> ·
                                {{ site_time($post->updated_at)?->diffForHumans(short: true) }}
                            </p>
                            @if ($post->status === App\Support\PostStatus::Rejected && filled($post->review_note))
                                <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ $post->review_note }}</p>
                            @endif
                        </div>
                        <flux:badge size="sm" :color="$post->status->badgeColor()">{{ $post->status->label() }}</flux:badge>
                    </div>
                @empty
                    <flux:text size="sm">You have not written anything yet.</flux:text>
                @endforelse
            </div>
        </flux:card>
    @endcan

    {{-- The review queue --}}
    @can('moderate posts')
        <flux:card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">Awaiting your review</flux:heading>
                    <flux:text size="sm" class="mt-0.5">
                        Submitted articles stay off the site until you approve them.
                    </flux:text>
                </div>

                <div class="flex items-center gap-3">
                    @if ($this->awaitingCount > 0)
                        <flux:badge color="amber">{{ $this->awaitingCount }} waiting</flux:badge>
                    @endif
                    <flux:link :href="route('admin.moderation')" wire:navigate class="text-sm">Open the queue</flux:link>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($this->reviewQueue as $post)
                    <div class="flex items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $post->title }}</p>
                            <p class="text-xs text-zinc-500">
                                {{ $post->submitter?->name ?? $post->author }} ·
                                <span class="capitalize">{{ $post->category->name }}</span> ·
                                {{ site_time($post->updated_at)?->diffForHumans(short: true) }}
                            </p>
                        </div>
                        <flux:button size="xs" variant="ghost" :href="route('admin.moderation')" wire:navigate>
                            Review
                        </flux:button>
                    </div>
                @empty
                    <flux:text size="sm">Nothing is waiting. Everything submitted has been dealt with.</flux:text>
                @endforelse
            </div>
        </flux:card>
    @endcan

    {{-- Site-wide figures, each shown only to whoever looks after that area --}}
    @canany(['moderate posts', 'manage gallery', 'manage messages', 'manage subscribers'])
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @can('moderate posts')
                <x-admin.stat-tile
                    label="Articles"
                    :value="$this->stats['posts']"
                    :meta="$this->stats['published'].' live · '.$this->awaitingCount.' awaiting review'"
                    icon="newspaper"
                    tone="brand"
                    :href="route('admin.moderation')" />
            @endcan

            @can('manage gallery')
                <x-admin.stat-tile
                    label="Gallery images"
                    :value="$this->stats['gallery']"
                    :meta="$this->imageFootprint.' on disk'"
                    icon="photo"
                    tone="leaf"
                    :href="route('admin.gallery')" />
            @endcan

            @can('manage messages')
                <x-admin.stat-tile
                    label="Messages"
                    :value="$this->stats['messages']"
                    :meta="$this->stats['unread'].' unread'"
                    icon="inbox"
                    tone="amber"
                    :href="route('admin.messages')" />
            @endcan

            @can('manage subscribers')
                <x-admin.stat-tile
                    label="Subscribers"
                    :value="$this->stats['subscribers']"
                    meta="Currently subscribed"
                    icon="users"
                    tone="sky"
                    :href="route('admin.subscribers')" />
            @endcan
        </div>
    @endcanany

    {{-- Activity chart --}}
    @canany(['moderate posts', 'manage messages'])
        <flux:card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg">Activity</flux:heading>
                    <flux:text size="sm" class="mt-0.5">Last 12 months</flux:text>
                </div>

                <div class="flex items-center gap-4 text-xs">
                    @can('moderate posts')
                        <span class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-sm bg-brand-500"></span> Articles
                        </span>
                    @endcan
                    @can('manage messages')
                        <span class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-sm bg-leaf-500"></span> Messages
                        </span>
                    @endcan
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
                            @can('moderate posts')
                                <div class="w-1/2 rounded-t bg-brand-500 transition-all group-hover:bg-brand-600"
                                     style="height: {{ max(2, round($month['posts'] / $peak * 100)) }}%"
                                     title="{{ $month['posts'] }} article(s)"></div>
                            @endcan
                            @can('manage messages')
                                <div class="w-1/2 rounded-t bg-leaf-500 transition-all group-hover:bg-leaf-600"
                                     style="height: {{ max(2, round($month['messages'] / $peak * 100)) }}%"
                                     title="{{ $month['messages'] }} message(s)"></div>
                            @endcan
                        </div>
                        <span class="text-[10px] text-zinc-500">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endcanany

    @canany(['moderate posts', 'manage messages'])
        <div class="grid gap-6 lg:grid-cols-2">
            @can('moderate posts')
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
            @endcan

            @can('manage messages')
                <flux:card>
                    <div class="flex items-center justify-between">
                        <flux:heading size="lg">Recent enquiries</flux:heading>
                        <flux:link :href="route('admin.messages')" wire:navigate class="text-sm">Inbox</flux:link>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($this->recentMessages as $message)
                            <div class="flex items-start gap-2">
                                <span @class([
                                    'mt-1.5 size-2 shrink-0 rounded-full',
                                    'bg-brand-600' => $message->read_at === null,
                                    'bg-zinc-300 dark:bg-zinc-700' => $message->read_at !== null,
                                ])></span>
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
            @endcan
        </div>
    @endcanany

    {{-- Shortcuts, one per area the viewer looks after --}}
    @canany(['manage services', 'manage classes', 'manage contact'])
        <flux:card>
            <flux:heading size="lg">Site content</flux:heading>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @can('manage services')
                    <a href="{{ route('admin.services') }}" wire:navigate
                       class="rounded-lg border border-zinc-200 p-4 transition hover:border-brand-400 dark:border-zinc-700">
                        <flux:icon.briefcase class="size-5 text-brand-600" />
                        <p class="mt-2 text-2xl font-bold">{{ $this->stats['services'] }}</p>
                        <p class="text-sm text-zinc-500">Service pages</p>
                    </a>
                @endcan

                @can('manage classes')
                    <a href="{{ route('admin.classes') }}" wire:navigate
                       class="rounded-lg border border-zinc-200 p-4 transition hover:border-leaf-400 dark:border-zinc-700">
                        <flux:icon.academic-cap class="size-5 text-leaf-600" />
                        <p class="mt-2 text-2xl font-bold">{{ $this->stats['classes'] }}</p>
                        <p class="text-sm text-zinc-500">Online classes</p>
                    </a>
                @endcan

                @can('manage contact')
                    <a href="{{ route('admin.contact') }}" wire:navigate
                       class="rounded-lg border border-zinc-200 p-4 transition hover:border-brand-400 dark:border-zinc-700">
                        <flux:icon.cog-6-tooth class="size-5 text-zinc-500" />
                        <p class="mt-2 text-sm font-semibold">Contact settings</p>
                        <p class="text-sm text-zinc-500">Copy &amp; details</p>
                    </a>
                @endcan

                <a href="{{ route('home') }}" target="_blank"
                   class="rounded-lg border border-zinc-200 p-4 transition hover:border-brand-400 dark:border-zinc-700">
                    <flux:icon.globe-alt class="size-5 text-zinc-500" />
                    <p class="mt-2 text-sm font-semibold">View website</p>
                    <p class="text-sm text-zinc-500">Opens in a new tab</p>
                </a>
            </div>
        </flux:card>
    @endcanany
</div>
