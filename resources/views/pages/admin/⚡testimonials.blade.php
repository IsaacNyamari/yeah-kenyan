<?php

use App\Concerns\ConfirmsActions;
use App\Models\Testimonial;
use App\Exceptions\ImageProcessingException;
use App\Services\ImageOptimizer;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Testimonials')] class extends Component {
    use ConfirmsActions;
    use WithFileUploads;

    public ?int $editingId = null;

    public string $client = '';

    public string $role = '';

    public string $quote = '';

    public ?TemporaryUploadedFile $photo = null;

    /**
     * @return Collection<int, Testimonial>
     */
    #[Computed]
    public function testimonials(): Collection
    {
        return Testimonial::orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * @return list<string>
     */
    public function confirmableActions(): array
    {
        return ['delete'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'client' => ['required', 'string', 'max:160'],
            'role' => ['nullable', 'string', 'max:160'],
            'quote' => ['required', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ];
    }

    public function save(ImageOptimizer $optimizer): void
    {
        $validated = $this->validate();

        $testimonial = $this->editingId ? Testimonial::findOrFail($this->editingId) : new Testimonial;

        if ($this->photo instanceof TemporaryUploadedFile) {
            try {
                // Portraits render at 112px, so there is no sense storing more.
                $image = $optimizer->store($this->photo, 'testimonials', maxWidth: 600);
            } catch (ImageProcessingException $e) {
                $this->addError('photo', $e->getMessage());

                return;
            }

            $optimizer->delete($testimonial->image);
            $testimonial->image = $image;
        }

        $testimonial->fill([
            'client' => $validated['client'],
            'role' => $validated['role'] ?: null,
            'quote' => $validated['quote'],
        ]);

        $testimonial->sort_order ??= (int) Testimonial::max('sort_order') + 1;

        $testimonial->save();

        $this->resetForm();

        Flux::toast(variant: 'success', heading: 'Saved', text: 'Testimonial saved.');
    }

    public function edit(int $id): void
    {
        $testimonial = Testimonial::findOrFail($id);

        $this->editingId = $testimonial->id;
        $this->client = $testimonial->client;
        $this->role = (string) $testimonial->role;
        $this->quote = $testimonial->quote;
        $this->photo = null;

        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        $testimonial = Testimonial::findOrFail($id);

        app(ImageOptimizer::class)->delete($testimonial->image);

        $testimonial->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        Flux::toast(variant: 'success', heading: 'Deleted', text: 'Testimonial removed.');
    }

    public function moveUp(int $id): void
    {
        $this->swap($id, -1);
    }

    public function moveDown(int $id): void
    {
        $this->swap($id, 1);
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'client', 'role', 'quote', 'photo');
        $this->resetValidation();
    }

    private function swap(int $id, int $direction): void
    {
        $all = Testimonial::orderBy('sort_order')->orderBy('id')->get();

        $index = $all->search(fn (Testimonial $t): bool => $t->id === $id);
        $target = $index + $direction;

        if ($index === false || $target < 0 || $target >= $all->count()) {
            return;
        }

        [$all[$index]->sort_order, $all[$target]->sort_order] = [$all[$target]->sort_order, $all[$index]->sort_order];

        $all[$index]->save();
        $all[$target]->save();

        unset($this->testimonials);
    }
}; ?>

<div class="space-y-6">
    <x-admin.confirm-modal :pending="$pendingAction" />

    <div>
        <flux:heading size="xl">Testimonials</flux:heading>
        <flux:text class="mt-1">Client quotes shown in the carousel on the homepage.</flux:text>
    </div>

    <div class="grid gap-8 xl:grid-cols-5">

        {{-- Editor --}}
        <div class="xl:col-span-2">
            <flux:card>
                <flux:heading size="lg">{{ $editingId ? 'Edit testimonial' : 'New testimonial' }}</flux:heading>

                <form wire:submit="save" class="mt-6 space-y-5">
                    <flux:input wire:model="client" label="Client name" placeholder="Church Events" required />
                    <flux:input wire:model="role" label="Rank / role" placeholder="Event Production"
                                description="Shown under the name" />
                    <flux:textarea wire:model="quote" label="Quote" rows="5" required />

                    <div>
                        <flux:input type="file" wire:model="photo" label="Photo" accept="image/*" />
                        <flux:text size="sm" class="mt-1">Shown as a circular portrait. Optimized on upload.</flux:text>

                        <div wire:loading wire:target="photo" class="mt-2 text-sm text-zinc-500">Uploading…</div>

                        @if ($photo && $photo->isPreviewable())
                            <img src="{{ $photo->temporaryUrl() }}" alt="Preview"
                                 class="mt-3 size-24 rounded-full object-cover">
                        @endif
                    </div>

                    <div class="flex gap-3">
                        <flux:button type="submit" variant="primary">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Add' }}</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </flux:button>

                        @if ($editingId)
                            <flux:button type="button" wire:click="resetForm" variant="ghost">Cancel</flux:button>
                        @endif
                    </div>
                </form>
            </flux:card>
        </div>

        {{-- Listing --}}
        <div class="xl:col-span-3">
            <flux:card>
                <flux:heading size="lg">All testimonials ({{ $this->testimonials->count() }})</flux:heading>

                <div class="mt-6 space-y-3">
                    @forelse ($this->testimonials as $testimonial)
                        <div @class([
                            'flex items-start gap-4 rounded-lg border p-3 transition',
                            'border-brand-500 bg-brand-50/50 dark:bg-brand-900/10' => $editingId === $testimonial->id,
                            'border-zinc-200 dark:border-zinc-700' => $editingId !== $testimonial->id,
                        ])>
                            @if ($testimonial->image)
                                <img src="{{ $testimonial->image_url }}" alt="" class="size-14 shrink-0 rounded-full object-cover">
                            @else
                                <div class="flex size-14 shrink-0 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <span class="text-lg font-bold text-zinc-400">
                                        {{ str($testimonial->client)->substr(0, 1)->upper() }}
                                    </span>
                                </div>
                            @endif

                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">{{ $testimonial->client }}</p>
                                @if ($testimonial->role)
                                    <p class="text-xs font-medium text-leaf-600">{{ $testimonial->role }}</p>
                                @endif
                                <p class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ $testimonial->quote }}</p>
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                <flux:button size="sm" variant="subtle" icon="chevron-up"
                                             wire:click="moveUp({{ $testimonial->id }})" aria-label="Move up" />
                                <flux:button size="sm" variant="subtle" icon="chevron-down"
                                             wire:click="moveDown({{ $testimonial->id }})" aria-label="Move down" />
                                <flux:button size="sm" variant="ghost" wire:click="edit({{ $testimonial->id }})">Edit</flux:button>
                                <flux:button size="sm" variant="danger" icon="trash"
                                             wire:click="confirmAction('delete', {{ $testimonial->id }}, {{ Js::from([
                                                 'heading' => 'Delete this testimonial?',
                                                 'text' => 'The quote from '.$testimonial->client.' will be removed from the homepage.',
                                                 'confirm' => 'Delete testimonial',
                                             ]) }})"
                                             aria-label="Delete" />
                            </div>
                        </div>
                    @empty
                        <flux:text>No testimonials yet.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        </div>
    </div>
</div>
