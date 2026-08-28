<?php

use App\Models\Post;
use App\Support\PostStatus;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Moderation')] class extends Component {
    use WithPagination;

    #[Url]
    public string $filter = 'pending';

    public ?int $selectedId = null;

    public string $note = '';

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    #[Computed]
    public function queue(): LengthAwarePaginator
    {
        return Post::query()
            ->with('category', 'submitter')
            ->when($this->filter !== 'all', fn ($query) => $query->where('status', $this->statusFilter()))
            ->latest('updated_at')
            ->paginate(12);
    }

    #[Computed]
    public function selected(): ?Post
    {
        return $this->selectedId
            ? Post::with('category', 'submitter', 'reviewer')->find($this->selectedId)
            : null;
    }

    #[Computed]
    public function pendingCount(): int
    {
        return Post::awaitingReview()->count();
    }

    private function statusFilter(): PostStatus
    {
        return PostStatus::tryFrom($this->filter) ?? PostStatus::Pending;
    }

    public function select(int $id): void
    {
        $this->selectedId = $id;
        $this->note = '';
        $this->resetValidation();
    }

    public function approve(int $id): void
    {
        $post = Post::findOrFail($id);

        $post->forceFill([
            'status' => PostStatus::Approved,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => null,
            // Approving is what puts an article on the site, so it gets a
            // publication date here unless one was already scheduled.
            'published_at' => $post->published_at ?? now(),
        ])->save();

        $this->afterReview($id);

        Flux::toast(variant: 'success', heading: 'Approved', text: 'The article is now live.');
    }

    public function reject(int $id): void
    {
        // A rejection an author cannot act on is just a disappearance, so the
        // reason is required rather than optional.
        $this->validate([
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ], attributes: ['note' => 'reason']);

        $post = Post::findOrFail($id);

        $post->forceFill([
            'status' => PostStatus::Rejected,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $this->note,
            // Takes it straight off the site if it had somehow been dated.
            'published_at' => null,
        ])->save();

        $this->afterReview($id);

        Flux::toast(variant: 'success', heading: 'Sent back', text: 'The author can see your reason and revise it.');
    }

    private function afterReview(int $id): void
    {
        $this->note = '';

        if ($this->selectedId === $id) {
            $this->selectedId = null;
        }

        unset($this->queue, $this->selected, $this->pendingCount);
    }

    public function updatedFilter(): void
    {
        $this->selectedId = null;
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Moderation</flux:heading>
            <flux:text class="mt-1">
                Articles submitted by authors wait here until you approve them. Nothing on this screen is on the
                public site yet.
            </flux:text>
        </div>

        @if ($this->pendingCount > 0)
            <flux:badge color="amber">{{ $this->pendingCount }} awaiting review</flux:badge>
        @else
            <flux:badge color="lime">Nothing waiting</flux:badge>
        @endif
    </div>

    <flux:radio.group wire:model.live="filter" variant="segmented" size="sm">
        <flux:radio value="pending" label="Awaiting review" />
        <flux:radio value="rejected" label="Sent back" />
        <flux:radio value="approved" label="Approved" />
        <flux:radio value="all" label="All" />
    </flux:radio.group>

    <div class="grid gap-6 lg:grid-cols-5">

        {{-- Queue --}}
        <div class="lg:col-span-2">
            <flux:card>
                <div class="space-y-2" wire:loading.class="opacity-50">
                    @forelse ($this->queue as $post)
                        <button type="button" wire:click="select({{ $post->id }})"
                                @class([
                                    'w-full rounded-lg border p-3 text-start transition',
                                    'border-brand-500 bg-brand-50/50 dark:bg-brand-900/10' => $selectedId === $post->id,
                                    'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' => $selectedId !== $post->id,
                                ])>
                            <div class="flex items-start gap-2">
                                <span class="min-w-0 flex-1 truncate text-sm font-semibold">{{ $post->title }}</span>
                                <flux:badge size="sm" :color="$post->status->badgeColor()">
                                    {{ $post->status->label() }}
                                </flux:badge>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">
                                {{ $post->submitter?->name ?? $post->author }}
                                &middot; <span class="capitalize">{{ $post->category->name }}</span>
                                &middot; {{ site_time($post->updated_at)?->diffForHumans(short: true) }}
                            </p>
                        </button>
                    @empty
                        <div class="py-10 text-center">
                            <flux:icon.check-circle class="mx-auto size-8 text-zinc-400" />
                            <flux:text class="mt-2">Nothing here.</flux:text>
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">{{ $this->queue->links() }}</div>
            </flux:card>
        </div>

        {{-- Review pane --}}
        <div class="lg:col-span-3">
            <flux:card>
                @if ($this->selected)
                    @php($post = $this->selected)

                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <flux:heading size="lg">{{ $post->title }}</flux:heading>
                            <flux:text class="mt-1">
                                {{ $post->submitter?->name ?? $post->author }}
                                &middot; <span class="capitalize">{{ $post->category->name }}</span>
                            </flux:text>
                        </div>

                        <flux:badge :color="$post->status->badgeColor()">{{ $post->status->label() }}</flux:badge>
                    </div>

                    @if ($post->image)
                        <img src="{{ $post->image_url }}" alt="" class="mt-4 h-48 w-full rounded-lg object-cover">
                    @endif

                    @if (filled($post->excerpt))
                        <flux:text class="mt-4 italic">{{ $post->excerpt }}</flux:text>
                    @endif

                    <flux:separator class="my-5" />

                    {{-- Sanitized on the way in by ArticleHtml, same as the public page. --}}
                    <div class="article-body">{!! $post->body !!}</div>

                    @if ($post->reviewed_at)
                        <flux:callout variant="secondary" class="mt-5">
                            <flux:callout.text>
                                Last reviewed by {{ $post->reviewer?->name ?? 'a moderator' }}
                                {{ site_time($post->reviewed_at)?->diffForHumans() }}.
                                @if (filled($post->review_note))
                                    Reason given: {{ $post->review_note }}
                                @endif
                            </flux:callout.text>
                        </flux:callout>
                    @endif

                    <flux:separator class="my-5" />

                    <div class="space-y-4">
                        <flux:textarea wire:model="note" rows="3"
                                       label="Reason for sending it back"
                                       description="Required to reject. The author sees this next to their article." />

                        <div class="flex flex-wrap gap-3">
                            @if ($post->status !== App\Support\PostStatus::Approved)
                                <flux:button variant="primary" wire:click="approve({{ $post->id }})">
                                    <span wire:loading.remove wire:target="approve">Approve and publish</span>
                                    <span wire:loading wire:target="approve">Publishing…</span>
                                </flux:button>
                            @endif

                            @if ($post->status !== App\Support\PostStatus::Rejected)
                                <flux:button variant="danger" wire:click="reject({{ $post->id }})">
                                    <span wire:loading.remove wire:target="reject">Send back</span>
                                    <span wire:loading wire:target="reject">Sending…</span>
                                </flux:button>
                            @endif

                            <flux:button variant="ghost" href="{{ route('admin.posts') }}" wire:navigate>
                                Open in editor
                            </flux:button>
                        </div>
                    </div>
                @else
                    <div class="py-16 text-center">
                        <flux:icon.document-check class="mx-auto size-10 text-zinc-400" />
                        <flux:text class="mt-3">Select an article to review it.</flux:text>
                    </div>
                @endif
            </flux:card>
        </div>
    </div>
</div>
