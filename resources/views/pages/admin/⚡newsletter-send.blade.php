<?php

use App\Models\Newsletter;
use App\Models\NewsletterSend;
use App\Models\Subscriber;
use App\Services\NewsletterDispatcher;
use App\Services\NewsletterRenderer;
use App\Support\NewsletterStatus;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Sends one issue, a batch at a time.
 *
 * The browser drives the loop: each poll sends a batch and reports back. That
 * keeps every request short enough to finish on shared hosting, where there is
 * no queue worker to hand the job to.
 */
new #[Layout('layouts.app')] #[Title('Send newsletter')] class extends Component {
    public Newsletter $newsletter;

    public string $testEmail = '';

    public bool $sending = false;

    public int $justSent = 0;

    public function mount(Newsletter $newsletter): void
    {
        $this->newsletter = $newsletter;
        $this->testEmail = (string) auth()->user()?->email;

        // A send interrupted by a closed tab is picked up where it stopped.
        $this->sending = $newsletter->status === NewsletterStatus::Sending
            && $this->progress['remaining'] > 0;
    }

    /**
     * @return array{total: int, sent: int, failed: int, remaining: int}
     */
    #[Computed]
    public function progress(): array
    {
        return app(NewsletterDispatcher::class)->progress($this->newsletter);
    }

    #[Computed]
    public function percentage(): int
    {
        $total = $this->progress['total'];

        return $total === 0 ? 0 : (int) round(($this->progress['sent'] + $this->progress['failed']) / $total * 100);
    }

    #[Computed]
    public function audienceCount(): int
    {
        return Subscriber::subscribed()->count();
    }

    /**
     * @return Collection<int, NewsletterSend>
     */
    #[Computed]
    public function failures(): Collection
    {
        return NewsletterSend::with('subscriber')
            ->where('newsletter_id', $this->newsletter->getKey())
            ->whereNotNull('failure')
            ->take(20)
            ->get();
    }

    #[Computed]
    public function preview(): string
    {
        return app(NewsletterRenderer::class)->render($this->newsletter);
    }

    public function sendTest(NewsletterDispatcher $dispatcher): void
    {
        $this->validate([
            'testEmail' => ['required', 'email'],
        ], attributes: ['testEmail' => 'email address']);

        try {
            $dispatcher->sendTest($this->newsletter, $this->testEmail);

            Flux::toast(variant: 'success', heading: 'Test sent', text: 'Check '.$this->testEmail.'.');
        } catch (\Throwable $e) {
            Flux::toast(
                variant: 'danger',
                heading: 'Could not send',
                text: str($e->getMessage())->limit(160)->toString(),
            );
        }
    }

    /**
     * Write down the recipient list and start the loop.
     */
    public function start(NewsletterDispatcher $dispatcher): void
    {
        abort_if($this->newsletter->status === NewsletterStatus::Sent, 403, 'This issue has already been sent.');

        if ($this->audienceCount === 0) {
            Flux::toast(variant: 'warning', heading: 'Nobody to send to', text: 'There are no subscribers on the list.');

            return;
        }

        $dispatcher->prepare($this->newsletter);

        $this->newsletter->refresh();
        $this->sending = true;

        unset($this->progress, $this->percentage);
    }

    /**
     * One batch per call, driven by wire:poll while sending is true.
     */
    public function sendNextBatch(NewsletterDispatcher $dispatcher): void
    {
        if (! $this->sending) {
            return;
        }

        $result = $dispatcher->sendChunk($this->newsletter);

        $this->justSent = $result['sent'];

        unset($this->progress, $this->percentage, $this->failures);

        if ($result['done']) {
            $this->sending = false;
            $this->newsletter->refresh();

            Flux::toast(
                variant: 'success',
                heading: 'Sent',
                text: 'The issue went out to '.$this->progress['sent'].' subscriber(s).',
            );
        }
    }

    public function pause(): void
    {
        $this->sending = false;

        Flux::toast(variant: 'success', text: 'Paused. Nobody will be sent a second copy when you resume.');
    }
}; ?>

<div class="space-y-6" @if ($sending) wire:poll.1500ms="sendNextBatch" @endif>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Send: {{ $newsletter->subject }}</flux:heading>
            <flux:text class="mt-1">
                <flux:badge size="sm" :color="$newsletter->status->badgeColor()">{{ $newsletter->status->label() }}</flux:badge>
            </flux:text>
        </div>

        <flux:button variant="ghost" :href="route('admin.newsletters')" wire:navigate>Back to newsletters</flux:button>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Sending --}}
        <div class="space-y-6">
            @if ($newsletter->status === App\Support\NewsletterStatus::Sent)
                <flux:card>
                    <flux:heading size="lg">This issue has gone out</flux:heading>
                    <flux:text class="mt-1">
                        Sent {{ site_time($newsletter->sent_at)?->format('M d, Y \a\t H:i') }} to
                        {{ $this->progress['sent'] }} subscriber{{ $this->progress['sent'] === 1 ? '' : 's' }}.
                    </flux:text>
                </flux:card>
            @else
                <flux:card>
                    <flux:heading size="lg">Send to the list</flux:heading>
                    <flux:text size="sm" class="mt-1">
                        {{ $this->audienceCount }} subscriber{{ $this->audienceCount === 1 ? '' : 's' }} will
                        receive this. Sending runs in batches, so leave this page open until it finishes.
                    </flux:text>

                    @if ($this->progress['total'] > 0)
                        <div class="mt-5">
                            <div class="flex items-center justify-between text-sm">
                                <span>{{ $this->progress['sent'] }} of {{ $this->progress['total'] }} sent</span>
                                <span class="text-zinc-500">{{ $this->percentage }}%</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-brand-500 transition-all"
                                     style="width: {{ $this->percentage }}%"></div>
                            </div>
                            @if ($this->progress['failed'] > 0)
                                <p class="mt-2 text-xs text-red-600 dark:text-red-400">
                                    {{ $this->progress['failed'] }} could not be delivered.
                                </p>
                            @endif
                        </div>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-3">
                        @if ($sending)
                            <flux:button variant="ghost" wire:click="pause">Pause</flux:button>
                            <flux:text size="sm" class="self-center">
                                Sending… {{ $this->progress['remaining'] }} to go
                            </flux:text>
                        @else
                            <flux:button variant="primary" wire:click="start">
                                <span wire:loading.remove wire:target="start">
                                    {{ $this->progress['remaining'] > 0 ? 'Resume sending' : 'Send now' }}
                                </span>
                                <span wire:loading wire:target="start">Preparing…</span>
                            </flux:button>
                        @endif
                    </div>

                    @unless ($sending)
                        <flux:callout variant="warning" class="mt-5">
                            <flux:callout.text>
                                There is no undo. Send a test to yourself first and read it in a real inbox.
                            </flux:callout.text>
                        </flux:callout>
                    @endunless
                </flux:card>
            @endif

            <flux:card>
                <flux:heading size="lg">Send a test</flux:heading>
                <flux:text size="sm" class="mt-1">
                    Goes to one address and is not recorded against anybody on the list.
                </flux:text>

                <form wire:submit="sendTest" class="mt-5 space-y-4">
                    <flux:input wire:model="testEmail" type="email" label="Email address" required />

                    <flux:button type="submit" variant="ghost">
                        <span wire:loading.remove wire:target="sendTest">Send test</span>
                        <span wire:loading wire:target="sendTest">Sending…</span>
                    </flux:button>
                </form>
            </flux:card>

            @if ($this->failures->isNotEmpty())
                <flux:card>
                    <flux:heading size="lg">Could not be delivered</flux:heading>

                    <div class="mt-4 space-y-2">
                        @foreach ($this->failures as $failure)
                            <div class="rounded-lg border border-red-200 p-3 text-sm dark:border-red-900">
                                <p class="font-medium">{{ $failure->subscriber?->email ?? 'Removed subscriber' }}</p>
                                <p class="text-xs text-zinc-500">{{ $failure->failure }}</p>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        </div>

        {{-- Preview --}}
        <flux:card>
            <flux:heading size="lg">What they will receive</flux:heading>

            <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <iframe srcdoc="{{ $this->preview }}" sandbox="" class="h-[640px] w-full bg-white"
                        title="Newsletter preview"></iframe>
            </div>
        </flux:card>
    </div>
</div>
