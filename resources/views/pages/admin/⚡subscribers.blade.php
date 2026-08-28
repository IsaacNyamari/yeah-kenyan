<?php

use App\Concerns\ConfirmsActions;
use App\Models\Subscriber;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('layouts.app')] #[Title('Subscribers')] class extends Component {
    use ConfirmsActions;
    use WithPagination;

    public string $search = '';

    #[Url]
    public string $filter = 'subscribed';

    public string $newEmail = '';

    public string $newName = '';

    /**
     * @return LengthAwarePaginator<int, Subscriber>
     */
    #[Computed]
    public function subscribers(): LengthAwarePaginator
    {
        return Subscriber::query()
            ->when($this->filter === 'subscribed', fn ($query) => $query->whereNull('unsubscribed_at'))
            ->when($this->filter === 'unsubscribed', fn ($query) => $query->whereNotNull('unsubscribed_at'))
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('email', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(20);
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        return [
            'subscribed' => Subscriber::subscribed()->count(),
            'unsubscribed' => Subscriber::whereNotNull('unsubscribed_at')->count(),
            'total' => Subscriber::count(),
        ];
    }

    /**
     * @return list<string>
     */
    public function confirmableActions(): array
    {
        return ['delete'];
    }

    public function add(): void
    {
        $validated = $this->validate([
            'newEmail' => ['required', 'email', 'max:255', 'unique:subscribers,email'],
            'newName' => ['nullable', 'string', 'max:120'],
        ], attributes: ['newEmail' => 'email address', 'newName' => 'name']);

        Subscriber::create([
            'email' => $validated['newEmail'],
            'name' => $validated['newName'] ?: null,
        ]);

        $this->reset('newEmail', 'newName');
        $this->refreshLists();

        Flux::toast(variant: 'success', heading: 'Added', text: 'They will receive the next issue.');
    }

    /**
     * Take somebody off the list without losing the record that they asked.
     */
    public function unsubscribe(int $id): void
    {
        Subscriber::findOrFail($id)->update(['unsubscribed_at' => now()]);

        $this->refreshLists();

        Flux::toast(variant: 'success', text: 'They will not receive any more issues.');
    }

    public function resubscribe(int $id): void
    {
        Subscriber::findOrFail($id)->update(['unsubscribed_at' => null]);

        $this->refreshLists();

        Flux::toast(variant: 'success', text: 'They are back on the list.');
    }

    public function delete(int $id): void
    {
        Subscriber::findOrFail($id)->delete();

        $this->refreshLists();

        Flux::toast(variant: 'success', heading: 'Deleted', text: 'The record was removed.');
    }

    /**
     * Hand the list back as a CSV.
     */
    public function export(): StreamedResponse
    {
        $filename = 'subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Email', 'Name', 'Status', 'Joined']);

            Subscriber::query()->orderBy('id')->chunk(500, function ($rows) use ($handle): void {
                foreach ($rows as $subscriber) {
                    fputcsv($handle, [
                        $subscriber->email,
                        $subscriber->name,
                        $subscriber->isSubscribed() ? 'Subscribed' : 'Unsubscribed',
                        site_time($subscriber->created_at)?->format('Y-m-d'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function refreshLists(): void
    {
        unset($this->subscribers, $this->counts);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <x-admin.confirm-modal :pending="$pendingAction" />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Subscribers</flux:heading>
            <flux:text class="mt-1">
                Everyone who signed up for the newsletter, and everyone who has since asked to stop.
            </flux:text>
        </div>

        <flux:button variant="ghost" icon="arrow-down-tray" wire:click="export">Export CSV</flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <flux:card>
            <p class="text-2xl font-bold">{{ $this->counts['subscribed'] }}</p>
            <p class="text-sm text-zinc-500">Currently subscribed</p>
        </flux:card>
        <flux:card>
            <p class="text-2xl font-bold">{{ $this->counts['unsubscribed'] }}</p>
            <p class="text-sm text-zinc-500">Unsubscribed</p>
        </flux:card>
        <flux:card>
            <p class="text-2xl font-bold">{{ $this->counts['total'] }}</p>
            <p class="text-sm text-zinc-500">On record</p>
        </flux:card>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">

        {{-- Add by hand --}}
        <div class="lg:col-span-2">
            <flux:card>
                <flux:heading size="lg">Add someone</flux:heading>
                <flux:text size="sm" class="mt-1">
                    For people who asked in person. Anyone signing up on the site is added automatically.
                </flux:text>

                <form wire:submit="add" class="mt-5 space-y-4">
                    <flux:input wire:model="newEmail" type="email" label="Email address" required />
                    <flux:input wire:model="newName" label="Name" description="Optional. Used to greet them." />

                    <flux:button type="submit" variant="primary">
                        <span wire:loading.remove wire:target="add">Add subscriber</span>
                        <span wire:loading wire:target="add">Adding…</span>
                    </flux:button>
                </form>
            </flux:card>
        </div>

        {{-- The list --}}
        <div class="lg:col-span-3">
            <flux:card>
                <div class="space-y-3">
                    <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass"
                                placeholder="Search by email or name" size="sm" />

                    <flux:radio.group wire:model.live="filter" variant="segmented" size="sm">
                        <flux:radio value="subscribed" label="Subscribed" />
                        <flux:radio value="unsubscribed" label="Unsubscribed" />
                        <flux:radio value="all" label="All" />
                    </flux:radio.group>
                </div>

                <div class="mt-4 space-y-2" wire:loading.class="opacity-50">
                    @forelse ($this->subscribers as $subscriber)
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $subscriber->email }}</p>
                                <p class="text-xs text-zinc-500">
                                    {{ $subscriber->name ?: 'No name' }} ·
                                    joined {{ site_time($subscriber->created_at)?->format('M d, Y') }}
                                </p>
                            </div>

                            @if ($subscriber->isSubscribed())
                                <flux:badge size="sm" color="lime">Subscribed</flux:badge>
                                <flux:button size="xs" variant="ghost"
                                             wire:click="unsubscribe({{ $subscriber->id }})">
                                    Unsubscribe
                                </flux:button>
                            @else
                                <flux:badge size="sm" color="zinc">Unsubscribed</flux:badge>
                                <flux:button size="xs" variant="ghost"
                                             wire:click="resubscribe({{ $subscriber->id }})">
                                    Resubscribe
                                </flux:button>
                            @endif

                            <flux:button size="xs" variant="danger" icon="trash" aria-label="Delete"
                                         wire:click="confirmAction('delete', {{ $subscriber->id }}, {{ Js::from([
                                             'heading' => 'Delete this record?',
                                             'text' => $subscriber->email.' will be removed entirely. If they asked to stop receiving mail, unsubscribe them instead — a deleted address can sign up again and start receiving mail.',
                                             'confirm' => 'Delete record',
                                         ]) }})" />
                        </div>
                    @empty
                        <flux:text size="sm">Nobody here yet.</flux:text>
                    @endforelse
                </div>

                <div class="mt-4">{{ $this->subscribers->links() }}</div>
            </flux:card>
        </div>
    </div>
</div>
