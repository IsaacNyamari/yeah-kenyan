<?php

use App\Concerns\ConfirmsActions;
use App\Models\ContactMessage;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Messages')] class extends Component {
    use ConfirmsActions;
    use WithPagination;

    public ?int $selectedId = null;

    public string $filter = 'all';

    public string $search = '';

    /**
     * @return LengthAwarePaginator<int, ContactMessage>
     */
    #[Computed]
    public function messages(): LengthAwarePaginator
    {
        return ContactMessage::query()
            ->when($this->filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('subject', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function selected(): ?ContactMessage
    {
        return $this->selectedId ? ContactMessage::find($this->selectedId) : null;
    }

    #[Computed]
    public function unreadCount(): int
    {
        return ContactMessage::whereNull('read_at')->count();
    }

    public function select(int $id): void
    {
        $this->selectedId = $id;

        $message = ContactMessage::findOrFail($id);

        if ($message->read_at === null) {
            $message->update(['read_at' => now()]);
            unset($this->unreadCount);
        }
    }

    public function markAllRead(): void
    {
        ContactMessage::whereNull('read_at')->update(['read_at' => now()]);

        unset($this->unreadCount);

        Flux::toast(variant: 'success', text: 'All messages marked as read.');
    }

    /**
     * @return list<string>
     */
    public function confirmableActions(): array
    {
        return ['delete', 'markAllRead'];
    }

    public function delete(int $id): void
    {
        ContactMessage::findOrFail($id)->delete();

        if ($this->selectedId === $id) {
            $this->selectedId = null;
        }

        Flux::toast(variant: 'success', heading: 'Deleted', text: 'The message was removed.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <x-admin.confirm-modal :pending="$pendingAction" />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Messages</flux:heading>
            <flux:text class="mt-1">Enquiries submitted through the website contact form.</flux:text>
        </div>

        @if ($this->unreadCount > 0)
            <flux:button variant="ghost" icon="check"
                         wire:click="confirmAction('markAllRead', null, {{ Js::from([
                             'heading' => 'Mark everything as read?',
                             'text' => 'All '.$this->unreadCount.' unread enquiries will be marked as read.',
                             'confirm' => 'Mark all read',
                             'variant' => 'info',
                         ]) }})">
                Mark all read ({{ $this->unreadCount }})
            </flux:button>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-5">

        {{-- Inbox list --}}
        <div class="lg:col-span-2">
            <flux:card>
                <div class="space-y-3">
                    <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass"
                                placeholder="Search messages…" size="sm" />

                    <flux:radio.group wire:model.live="filter" variant="segmented" size="sm">
                        <flux:radio value="all" label="All" />
                        <flux:radio value="unread" label="Unread" />
                    </flux:radio.group>
                </div>

                <div class="mt-4 space-y-2" wire:loading.class="opacity-50">
                    @forelse ($this->messages as $message)
                        <button type="button" wire:click="select({{ $message->id }})"
                                @class([
                                    'w-full rounded-lg border p-3 text-start transition',
                                    'border-brand-500 bg-brand-50/50 dark:bg-brand-900/10' => $selectedId === $message->id,
                                    'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' => $selectedId !== $message->id,
                                ])>
                            <div class="flex items-center gap-2">
                                @if ($message->read_at === null)
                                    <span class="size-2 shrink-0 rounded-full bg-brand-600"></span>
                                @endif
                                <span @class(['truncate text-sm', 'font-bold' => $message->read_at === null])>
                                    {{ $message->name }}
                                </span>
                                <span class="ms-auto shrink-0 text-xs text-zinc-500">
                                    {{ site_time($message->created_at)?->diffForHumans(short: true) }}
                                </span>
                            </div>
                            <p class="mt-1 truncate text-sm text-zinc-600 dark:text-zinc-400">{{ $message->subject }}</p>
                        </button>
                    @empty
                        <flux:text size="sm">No messages{{ $filter === 'unread' ? ' unread' : '' }}.</flux:text>
                    @endforelse
                </div>

                <div class="mt-4">{{ $this->messages->links() }}</div>
            </flux:card>
        </div>

        {{-- Reading pane --}}
        <div class="lg:col-span-3">
            <flux:card>
                @if ($this->selected)
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <flux:heading size="lg">{{ $this->selected->subject }}</flux:heading>
                            <flux:text class="mt-1">
                                {{ $this->selected->name }} &middot;
                                <a href="mailto:{{ $this->selected->email }}" class="underline">{{ $this->selected->email }}</a>
                            </flux:text>
                            <flux:text size="sm" class="mt-0.5">
                                {{ site_time($this->selected->created_at)?->format('M d, Y \a\t H:i') }}
                            </flux:text>
                        </div>

                        <div class="flex shrink-0 gap-2">
                            <flux:button size="sm" variant="ghost" icon="arrow-uturn-left"
                                         href="mailto:{{ $this->selected->email }}?subject={{ rawurlencode('Re: '.$this->selected->subject) }}">
                                Reply
                            </flux:button>
                            <flux:button size="sm" variant="danger" icon="trash"
                                         wire:click="confirmAction('delete', {{ $this->selected->id }}, {{ Js::from([
                                             'heading' => 'Delete this message?',
                                             'text' => 'The enquiry from '.$this->selected->name.' will be permanently removed.',
                                             'confirm' => 'Delete message',
                                         ]) }})"
                                         aria-label="Delete message" />
                        </div>
                    </div>

                    <flux:separator class="my-5" />

                    <div class="text-sm leading-relaxed whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $this->selected->message }}</div>
                @else
                    <div class="py-16 text-center">
                        <flux:icon.inbox class="mx-auto size-10 text-zinc-400" />
                        <flux:text class="mt-3">Select a message to read it.</flux:text>
                    </div>
                @endif
            </flux:card>
        </div>
    </div>
</div>
