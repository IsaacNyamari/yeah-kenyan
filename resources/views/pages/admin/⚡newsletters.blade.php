<?php

use App\Concerns\ConfirmsActions;
use App\Models\Newsletter;
use App\Models\NewsletterTemplate;
use App\Models\Subscriber;
use App\Services\ArticleHtml;
use App\Services\NewsletterRenderer;
use App\Support\NewsletterStatus;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Newsletters')] class extends Component {
    use ConfirmsActions;
    use WithPagination;

    public ?int $editingId = null;

    public string $subject = '';

    public string $preheader = '';

    public string $body = '';

    public ?int $newsletter_template_id = null;

    public function mount(): void
    {
        $this->newsletter_template_id = NewsletterTemplate::where('is_default', true)->value('id')
            ?? NewsletterTemplate::value('id');
    }

    /**
     * @return LengthAwarePaginator<int, Newsletter>
     */
    #[Computed]
    public function newsletters(): LengthAwarePaginator
    {
        return Newsletter::with('template', 'author')
            ->withCount([
                'sends as delivered_count' => fn ($query) => $query->whereNotNull('sent_at'),
            ])
            ->latest()
            ->paginate(10);
    }

    /**
     * @return Collection<int, NewsletterTemplate>
     */
    #[Computed]
    public function templates(): Collection
    {
        return NewsletterTemplate::orderByDesc('is_default')->orderBy('name')->get();
    }

    #[Computed]
    public function subscriberCount(): int
    {
        return Subscriber::subscribed()->count();
    }

    /**
     * The issue being written, rendered inside its template.
     */
    #[Computed]
    public function preview(): string
    {
        $draft = new Newsletter([
            'subject' => $this->subject ?: 'Your subject line',
            'preheader' => $this->preheader,
            'body' => $this->body ?: '<p>Start writing and this preview follows along.</p>',
        ]);

        $draft->setRelation(
            'template',
            $this->newsletter_template_id
                ? NewsletterTemplate::find($this->newsletter_template_id)
                : null,
        );

        return app(NewsletterRenderer::class)->render($draft);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:200'],
            'preheader' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:100000'],
            'newsletter_template_id' => ['nullable', 'exists:newsletter_templates,id'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $newsletter = $this->editingId ? Newsletter::findOrFail($this->editingId) : new Newsletter;

        // A sent issue is a record of what went out. Letting it be rewritten
        // afterwards would make that record a lie.
        abort_unless($newsletter->isEditable() || ! $newsletter->exists, 403, 'This issue has already been sent.');

        $newsletter->fill([
            'subject' => $validated['subject'],
            'preheader' => $validated['preheader'] ?: null,
            // Rendered unescaped inside the email, so sanitized on the way in.
            'body' => app(ArticleHtml::class)->sanitize($validated['body']),
            'newsletter_template_id' => $validated['newsletter_template_id'],
        ]);

        $newsletter->created_by ??= auth()->id();
        $newsletter->status ??= NewsletterStatus::Draft;

        $newsletter->save();

        $this->resetForm();

        unset($this->newsletters);

        Flux::toast(variant: 'success', heading: 'Saved', text: 'The issue was saved as a draft.');
    }

    public function edit(int $id): void
    {
        $newsletter = Newsletter::findOrFail($id);

        abort_unless($newsletter->isEditable(), 403, 'This issue has already been sent.');

        $this->editingId = $newsletter->id;
        $this->subject = $newsletter->subject;
        $this->preheader = (string) $newsletter->preheader;
        $this->body = $newsletter->body;
        $this->newsletter_template_id = $newsletter->newsletter_template_id;

        $this->resetValidation();
    }

    /**
     * @return list<string>
     */
    public function confirmableActions(): array
    {
        return ['delete'];
    }

    public function delete(int $id): void
    {
        Newsletter::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        unset($this->newsletters);

        Flux::toast(variant: 'success', heading: 'Deleted', text: 'The issue was removed.');
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'subject', 'preheader', 'body');
        $this->newsletter_template_id = NewsletterTemplate::where('is_default', true)->value('id')
            ?? NewsletterTemplate::value('id');
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <x-admin.confirm-modal :pending="$pendingAction" />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Newsletters</flux:heading>
            <flux:text class="mt-1">
                Write an issue, check the preview, then send it to
                {{ $this->subscriberCount }} subscriber{{ $this->subscriberCount === 1 ? '' : 's' }}.
            </flux:text>
        </div>

        <flux:button variant="ghost" icon="swatch" :href="route('admin.newsletter-templates')" wire:navigate>
            Templates
        </flux:button>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">

        {{-- Composer --}}
        <flux:card>
            <flux:heading size="lg">{{ $editingId ? 'Edit issue' : 'New issue' }}</flux:heading>

            <form wire:submit="save" class="mt-5 space-y-4">
                <flux:input wire:model.live.debounce.500ms="subject" label="Subject" required
                            placeholder="What is in this issue?" />

                <flux:input wire:model.live.debounce.500ms="preheader" label="Preheader"
                            description="The short line some mail clients show after the subject." />

                <flux:select wire:model.live="newsletter_template_id" label="Template"
                             placeholder="Plain wrapper">
                    @foreach ($this->templates as $template)
                        <flux:select.option value="{{ $template->id }}">
                            {{ $template->name }}{{ $template->is_default ? ' (default)' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model.live.debounce.600ms="body" label="Body" rows="14"
                               description="Paragraphs, headings, lists, bold and italics. Anything else is stripped." />

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Save draft' }}</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </flux:button>

                    @if ($editingId)
                        <flux:button type="button" variant="ghost" wire:click="resetForm">Cancel</flux:button>
                    @endif
                </div>
            </form>
        </flux:card>

        {{-- Preview --}}
        <flux:card>
            <flux:heading size="lg">Preview</flux:heading>
            <flux:text size="sm" class="mt-1">How it will land in an inbox.</flux:text>

            <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <iframe srcdoc="{{ $this->preview }}" sandbox="" class="h-[560px] w-full bg-white"
                        title="Newsletter preview"></iframe>
            </div>
        </flux:card>
    </div>

    {{-- Past and pending issues --}}
    <flux:card>
        <flux:heading size="lg">Issues</flux:heading>

        <div class="mt-4 space-y-2">
            @forelse ($this->newsletters as $issue)
                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-sm font-medium">{{ $issue->subject }}</span>
                            <flux:badge size="sm" :color="$issue->status->badgeColor()">{{ $issue->status->label() }}</flux:badge>
                        </div>
                        <p class="truncate text-xs text-zinc-500">
                            {{ $issue->template?->name ?? 'Plain wrapper' }} ·
                            {{ $issue->author?->name ?? 'Unknown' }} ·
                            @if ($issue->sent_at)
                                sent {{ site_time($issue->sent_at)?->format('M d, Y') }} to {{ $issue->delivered_count }}
                            @else
                                saved {{ site_time($issue->updated_at)?->diffForHumans(short: true) }}
                            @endif
                        </p>
                    </div>

                    @if ($issue->isEditable())
                        <flux:button size="xs" variant="ghost" wire:click="edit({{ $issue->id }})">Edit</flux:button>
                    @endif

                    <flux:button size="xs" variant="primary"
                                 :href="route('admin.newsletter-send', $issue)" wire:navigate>
                        {{ $issue->status === App\Support\NewsletterStatus::Sending ? 'Resume' : 'Send' }}
                    </flux:button>

                    <flux:button size="xs" variant="danger" icon="trash" aria-label="Delete"
                                 wire:click="confirmAction('delete', {{ $issue->id }}, {{ Js::from([
                                     'heading' => 'Delete this issue?',
                                     'text' => Str::limit($issue->subject, 70).' will be removed, along with its delivery record.',
                                     'confirm' => 'Delete issue',
                                 ]) }})" />
                </div>
            @empty
                <flux:text size="sm">No issues yet.</flux:text>
            @endforelse
        </div>

        <div class="mt-4">{{ $this->newsletters->links() }}</div>
    </flux:card>
</div>
