<?php

use App\Concerns\ConfirmsActions;
use App\Models\Newsletter;
use App\Models\NewsletterTemplate;
use App\Services\NewsletterRenderer;
use App\Support\NewsletterStatus;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Newsletter templates')] class extends Component {
    use ConfirmsActions;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $html = '';

    public bool $is_default = false;

    public function mount(): void
    {
        $this->html = NewsletterRenderer::STARTER_HTML;
    }

    /**
     * @return Collection<int, NewsletterTemplate>
     */
    #[Computed]
    public function templates(): Collection
    {
        return NewsletterTemplate::withCount('newsletters')->orderByDesc('is_default')->orderBy('name')->get();
    }

    /**
     * The placeholders a template may use, shown next to the editor.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function placeholders(): array
    {
        return [
            '{{ content }}' => "The issue's writing. Every template needs this one.",
            '{{ subject }}' => 'The subject line.',
            '{{ preheader }}' => 'The short line some mail clients show under the subject.',
            '{{ name }}' => "The recipient's name, or “there” when you do not have one.",
            '{{ email }}' => "The recipient's address.",
            '{{ site_name }}' => 'Your site name from Settings.',
            '{{ site_url }}' => 'A link back to the site.',
            '{{ year }}' => 'The current year, for a copyright line.',
            '{{ unsubscribe_url }}' => 'Their unsubscribe link. Required by law in most places, and mail providers look for it.',
        ];
    }

    /**
     * A live preview of the template being edited, with sample copy in place
     * of a real issue.
     */
    #[Computed]
    public function preview(): string
    {
        $sample = new Newsletter([
            'subject' => 'A sample subject line',
            'preheader' => 'The short line under the subject',
            'body' => '<p>This is roughly how a paragraph of your writing will sit inside the template.</p>'
                .'<p>Change the design on the left and this updates as you type.</p>',
        ]);

        $sample->setRelation('template', new NewsletterTemplate(['name' => 'Preview', 'html' => $this->html]));

        return app(NewsletterRenderer::class)->render($sample);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            // Without the content placeholder a template would send the design
            // and drop the writing, which is the one failure nobody notices
            // until it has already gone out.
            'html' => ['required', 'string', 'max:200000'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if (! str_contains($this->html, '{{ content }}')) {
            $this->addError('html', 'The template must include {{ content }} somewhere, or the writing will not appear.');

            return;
        }

        $template = $this->editingId
            ? NewsletterTemplate::findOrFail($this->editingId)
            : new NewsletterTemplate;

        $template->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'html' => $validated['html'],
            'is_default' => $this->is_default,
        ])->save();

        if ($this->is_default) {
            // Exactly one default, or "the default" means nothing.
            NewsletterTemplate::whereKeyNot($template->getKey())->update(['is_default' => false]);
        }

        $this->resetForm();

        unset($this->templates);

        Flux::toast(variant: 'success', heading: 'Saved', text: 'The template was saved.');
    }

    public function edit(int $id): void
    {
        $template = NewsletterTemplate::findOrFail($id);

        $this->editingId = $template->id;
        $this->name = $template->name;
        $this->description = (string) $template->description;
        $this->html = $template->html;
        $this->is_default = $template->is_default;

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
        $template = NewsletterTemplate::findOrFail($id);

        // Issues keep their own copy of nothing — they reference the template —
        // so a sent issue would lose its design. The reference is nulled rather
        // than blocked, and the renderer falls back to a plain wrapper.
        $template->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        unset($this->templates);

        Flux::toast(variant: 'success', heading: 'Deleted', text: 'The template was removed.');
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'name', 'description', 'is_default');
        $this->html = NewsletterRenderer::STARTER_HTML;
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <x-admin.confirm-modal :pending="$pendingAction" />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Newsletter templates</flux:heading>
            <flux:text class="mt-1">
                The wrapper around an issue: masthead, colours and footer. Write it once and reuse it.
            </flux:text>
        </div>

        <flux:button variant="ghost" :href="route('admin.newsletters')" wire:navigate>Back to newsletters</flux:button>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">

        {{-- Editor --}}
        <div class="space-y-6">
            <flux:card>
                <flux:heading size="lg">{{ $editingId ? 'Edit template' : 'New template' }}</flux:heading>

                <form wire:submit="save" class="mt-5 space-y-4">
                    <flux:input wire:model="name" label="Name" placeholder="Monthly roundup" required />

                    <flux:input wire:model="description" label="Description"
                                description="A note to yourself about when to use this one." />

                    <flux:textarea wire:model.live.debounce.500ms="html" label="HTML" rows="18"
                                   class="font-mono text-xs"
                                   description="Email clients only support simple, table-based HTML with inline styles." />

                    <flux:checkbox wire:model="is_default" label="Use this by default for new issues" />

                    <div class="flex gap-3">
                        <flux:button type="submit" variant="primary">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update template' : 'Create template' }}</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </flux:button>

                        @if ($editingId)
                            <flux:button type="button" variant="ghost" wire:click="resetForm">Cancel</flux:button>
                        @endif
                    </div>
                </form>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">Placeholders</flux:heading>
                <flux:text size="sm" class="mt-1">
                    Anything in double braces is swapped for the real thing when an issue is sent.
                </flux:text>

                <dl class="mt-4 space-y-2 text-sm">
                    @foreach ($this->placeholders as $token => $meaning)
                        <div class="flex flex-col gap-0.5 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <dt class="font-mono text-xs text-brand-600">{{ $token }}</dt>
                            <dd class="text-xs text-zinc-500">{{ $meaning }}</dd>
                        </div>
                    @endforeach
                </dl>
            </flux:card>
        </div>

        {{-- Preview and existing templates --}}
        <div class="space-y-6">
            <flux:card>
                <flux:heading size="lg">Preview</flux:heading>
                <flux:text size="sm" class="mt-1">Sample copy, your design.</flux:text>

                <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                    {{-- Sandboxed: a template is arbitrary HTML written by an
                         editor, so it is kept out of the dashboard's own document. --}}
                    <iframe srcdoc="{{ $this->preview }}" sandbox="" class="h-[520px] w-full bg-white"
                            title="Template preview"></iframe>
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">Saved templates</flux:heading>

                <div class="mt-4 space-y-2">
                    @forelse ($this->templates as $template)
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-sm font-medium">{{ $template->name }}</span>
                                    @if ($template->is_default)
                                        <flux:badge size="sm" color="lime">Default</flux:badge>
                                    @endif
                                </div>
                                <p class="truncate text-xs text-zinc-500">
                                    {{ $template->description ?: 'No description' }} ·
                                    used by {{ $template->newsletters_count }} issue{{ $template->newsletters_count === 1 ? '' : 's' }}
                                </p>
                            </div>

                            <flux:button size="xs" variant="ghost" wire:click="edit({{ $template->id }})">Edit</flux:button>

                            <flux:button size="xs" variant="danger" icon="trash" aria-label="Delete"
                                         wire:click="confirmAction('delete', {{ $template->id }}, {{ Js::from([
                                             'heading' => 'Delete this template?',
                                             'text' => $template->name.' will be removed. Issues using it fall back to a plain wrapper.',
                                             'confirm' => 'Delete template',
                                         ]) }})" />
                        </div>
                    @empty
                        <flux:text size="sm">No templates yet. The one on the left is a starting point.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        </div>
    </div>
</div>
