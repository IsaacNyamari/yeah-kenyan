<?php

use App\Models\Subscriber;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The other end of the unsubscribe link in every issue.
 *
 * The token is the only thing needed, on purpose: someone who wants off a
 * mailing list should not have to sign in to get off it.
 */
new #[Layout('layouts.site')] #[Title('Unsubscribe')] class extends Component {
    public ?Subscriber $subscriber = null;

    public bool $done = false;

    public function mount(string $token): void
    {
        $this->subscriber = Subscriber::where('token', $token)->first();

        // Already off the list: say so rather than showing a button that
        // appears to do nothing.
        $this->done = $this->subscriber !== null && ! $this->subscriber->isSubscribed();
    }

    public function unsubscribe(): void
    {
        if ($this->subscriber === null) {
            return;
        }

        $this->subscriber->update(['unsubscribed_at' => now()]);

        $this->done = true;
    }

    public function resubscribe(): void
    {
        if ($this->subscriber === null) {
            return;
        }

        $this->subscriber->update(['unsubscribed_at' => null]);

        $this->done = false;
    }
}; ?>

<div class="mx-auto max-w-xl px-4 py-24">
    <div class="rounded-2xl border border-zinc-200 p-8 text-center dark:border-zinc-800">
        @if ($subscriber === null)
            <flux:heading size="xl">Link not recognised</flux:heading>
            <flux:text class="mt-3">
                That unsubscribe link does not match anyone on our list. It may already have been used.
            </flux:text>
        @elseif ($done)
            <flux:heading size="xl">You are unsubscribed</flux:heading>
            <flux:text class="mt-3">
                We will not send any more newsletters to {{ $subscriber->email }}.
            </flux:text>

            <flux:button class="mt-6" variant="ghost" wire:click="resubscribe">
                Changed your mind? Resubscribe
            </flux:button>
        @else
            <flux:heading size="xl">Unsubscribe</flux:heading>
            <flux:text class="mt-3">
                Stop sending the newsletter to {{ $subscriber->email }}?
            </flux:text>

            <flux:button class="mt-6" variant="primary" wire:click="unsubscribe">
                <span wire:loading.remove wire:target="unsubscribe">Yes, unsubscribe me</span>
                <span wire:loading wire:target="unsubscribe">Updating…</span>
            </flux:button>
        @endif

        <div class="mt-8">
            <flux:link :href="route('home')" wire:navigate>Back to the website</flux:link>
        </div>
    </div>
</div>
