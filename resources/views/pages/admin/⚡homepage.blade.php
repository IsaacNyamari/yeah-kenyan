<?php

use App\Concerns\ConfirmsActions;
use App\Concerns\PicksGalleryImages;
use App\Exceptions\ImageProcessingException;
use App\Models\HeroPanel;
use App\Services\ImageOptimizer;
use App\Support\HeroPanelKind;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Homepage')] class extends Component {
    use ConfirmsActions;
    use PicksGalleryImages;
    use WithFileUploads;

    public ?int $editingId = null;

    public string $kind = 'slide';

    public string $badge = '';

    public string $text = '';

    public bool $is_published = true;

    public ?TemporaryUploadedFile $photo = null;

    /**
     * @return Collection<int, HeroPanel>
     */
    #[Computed]
    public function slides(): Collection
    {
        return HeroPanel::ofKind(HeroPanelKind::Slide)->get();
    }

    /**
     * @return Collection<int, HeroPanel>
     */
    #[Computed]
    public function tiles(): Collection
    {
        return HeroPanel::ofKind(HeroPanelKind::Tile)->get();
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
            'kind' => ['required', 'in:slide,tile'],
            'badge' => ['required', 'string', 'max:60'],
            'text' => ['required', 'string', 'max:500'],
            // A panel is mostly its image, so one is required unless the record
            // already has one or a gallery image has been picked.
            'photo' => [$this->editingId || filled($this->galleryImage) ? 'nullable' : 'required', 'image', 'max:8192'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['badge' => 'label', 'text' => 'caption', 'photo' => 'image'];
    }

    public function save(ImageOptimizer $optimizer): void
    {
        $validated = $this->validate();

        $panel = $this->editingId ? HeroPanel::findOrFail($this->editingId) : new HeroPanel;

        if ($this->photo instanceof TemporaryUploadedFile) {
            try {
                $image = $optimizer->store($this->photo, 'hero');
            } catch (ImageProcessingException $e) {
                $this->addError('photo', $e->getMessage());

                return;
            }

            $this->detachImage($panel->image, $optimizer, $panel);
            $panel->image = $image;
        } elseif (filled($this->galleryImage)) {
            // Reused as-is: the file is shared with the gallery, not copied.
            $this->detachImage($panel->image, $optimizer, $panel);
            $panel->image = $this->galleryImage;
        }

        $panel->fill([
            'kind' => $validated['kind'],
            'badge' => $validated['badge'],
            'text' => $validated['text'],
            'is_published' => $this->is_published,
        ]);

        $panel->sort_order ??= (int) HeroPanel::where('kind', $validated['kind'])->max('sort_order') + 1;

        $panel->save();

        $this->resetForm();

        unset($this->slides, $this->tiles);

        Flux::toast(variant: 'success', heading: 'Saved', text: 'The homepage was updated.');
    }

    public function edit(int $id): void
    {
        $panel = HeroPanel::findOrFail($id);

        $this->editingId = $panel->id;
        $this->kind = $panel->kind->value;
        $this->badge = $panel->badge;
        $this->text = $panel->text;
        $this->is_published = $panel->is_published;
        $this->photo = null;
        $this->galleryImage = null;
        $this->currentImage = $panel->image;

        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        $optimizer = app(ImageOptimizer::class);

        $panel = HeroPanel::findOrFail($id);

        $this->detachImage($panel->image, $optimizer, $panel);

        $panel->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        unset($this->slides, $this->tiles);

        Flux::toast(variant: 'success', heading: 'Deleted', text: 'The panel was removed.');
    }

    public function togglePublished(int $id): void
    {
        $panel = HeroPanel::findOrFail($id);
        $panel->update(['is_published' => ! $panel->is_published]);

        unset($this->slides, $this->tiles);

        Flux::toast(
            variant: 'success',
            text: $panel->is_published ? 'Panel is now visible.' : 'Panel is now hidden.',
        );
    }

    public function moveUp(int $id): void
    {
        $this->swapWithNeighbour($id, direction: -1);
    }

    public function moveDown(int $id): void
    {
        $this->swapWithNeighbour($id, direction: 1);
    }

    /**
     * Reorder by swapping sort_order with the adjacent panel of the same kind.
     */
    private function swapWithNeighbour(int $id, int $direction): void
    {
        $panel = HeroPanel::findOrFail($id);

        $siblings = HeroPanel::ofKind($panel->kind)->get();

        $index = $siblings->search(fn (HeroPanel $item): bool => $item->id === $id);
        $target = $index + $direction;

        if ($index === false || $target < 0 || $target >= $siblings->count()) {
            return;
        }

        $current = $siblings[$index];
        $neighbour = $siblings[$target];

        [$current->sort_order, $neighbour->sort_order] = [$neighbour->sort_order, $current->sort_order];

        $current->save();
        $neighbour->save();

        unset($this->slides, $this->tiles);
    }

    public function startNew(string $kind): void
    {
        $this->resetForm();
        $this->kind = HeroPanelKind::tryFrom($kind)?->value ?? HeroPanelKind::Slide->value;
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'badge', 'text', 'photo', 'galleryImage', 'currentImage');
        $this->is_published = true;
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <x-admin.confirm-modal :pending="$pendingAction" />

    <div>
        <flux:heading size="xl">Homepage</flux:heading>
        <flux:text class="mt-1">
            The banner at the top of the site: a rotating panel on the left and fixed tiles beside it.
        </flux:text>
    </div>

    <div class="grid gap-6 xl:grid-cols-5">

        {{-- Editor --}}
        <div class="xl:col-span-2">
            <flux:card>
                <flux:heading size="lg">{{ $editingId ? 'Edit panel' : 'New panel' }}</flux:heading>

                <form wire:submit="save" class="mt-5 space-y-5">
                    <flux:radio.group wire:model="kind" variant="segmented" size="sm" label="Where it goes">
                        <flux:radio value="slide" label="Rotating banner" />
                        <flux:radio value="tile" label="Side tile" />
                    </flux:radio.group>

                    <flux:text size="sm">
                        {{ App\Support\HeroPanelKind::from($kind)->description() }}
                    </flux:text>

                    <flux:input wire:model="badge" label="Label"
                                description="The small tag over the caption, e.g. Branding." required />

                    <flux:textarea wire:model="text" label="Caption" rows="3"
                                   description="One line. It is shown in capitals over the image." required />

                    <x-admin.image-field
                        label="Image"
                        description="Landscape works best. Text sits over the bottom of it."
                        :photo="$photo"
                        :galleryImage="$galleryImage"
                        :currentUrl="$currentImage ? App\Models\HeroPanel::urlFor($currentImage) : null"
                        :picking="$pickingFromGallery"
                        :choices="$this->galleryChoices"
                        :collections="$this->galleryCollections"
                        :collection="$galleryCollection" />

                    <flux:checkbox wire:model="is_published" label="Show on the homepage" />

                    <div class="flex gap-3">
                        <flux:button type="submit" variant="primary">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update panel' : 'Add panel' }}</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </flux:button>

                        @if ($editingId)
                            <flux:button type="button" variant="ghost" wire:click="resetForm">Cancel</flux:button>
                        @endif
                    </div>
                </form>
            </flux:card>
        </div>

        {{-- Current panels --}}
        <div class="space-y-6 xl:col-span-3">
            @foreach ([['kind' => App\Support\HeroPanelKind::Slide, 'panels' => $this->slides], ['kind' => App\Support\HeroPanelKind::Tile, 'panels' => $this->tiles]] as $group)
                <flux:card>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <flux:heading size="lg">{{ $group['kind']->label() }}s</flux:heading>
                            <flux:text size="sm" class="mt-0.5">{{ $group['kind']->description() }}</flux:text>
                        </div>

                        <flux:button size="sm" variant="ghost" icon="plus"
                                     wire:click="startNew('{{ $group['kind']->value }}')">
                            Add
                        </flux:button>
                    </div>

                    <div class="mt-4 space-y-2">
                        @forelse ($group['panels'] as $panel)
                            <div @class([
                                'flex items-center gap-3 rounded-lg border p-3',
                                'border-zinc-200 dark:border-zinc-700' => $panel->is_published,
                                'border-dashed border-zinc-300 opacity-60 dark:border-zinc-600' => ! $panel->is_published,
                            ])>
                                <img src="{{ $panel->image_url }}" alt=""
                                     class="size-16 shrink-0 rounded object-cover">

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold">{{ $panel->badge }}</span>
                                        @unless ($panel->is_published)
                                            <flux:badge size="sm" color="zinc">Hidden</flux:badge>
                                        @endunless
                                    </div>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-zinc-500">{{ $panel->text }}</p>
                                </div>

                                <div class="flex shrink-0 items-center gap-1">
                                    <flux:button size="xs" variant="ghost" icon="chevron-up"
                                                 aria-label="Move up" wire:click="moveUp({{ $panel->id }})" />
                                    <flux:button size="xs" variant="ghost" icon="chevron-down"
                                                 aria-label="Move down" wire:click="moveDown({{ $panel->id }})" />
                                    <flux:button size="xs" variant="ghost"
                                                 :icon="$panel->is_published ? 'eye' : 'eye-slash'"
                                                 aria-label="Toggle visibility"
                                                 wire:click="togglePublished({{ $panel->id }})" />
                                    <flux:button size="xs" variant="ghost" wire:click="edit({{ $panel->id }})">Edit</flux:button>
                                    <flux:button size="xs" variant="danger" icon="trash" aria-label="Delete"
                                                 wire:click="confirmAction('delete', {{ $panel->id }}, {{ Js::from([
                                                     'heading' => 'Delete this panel?',
                                                     'text' => $panel->badge.' will be removed from the homepage.',
                                                     'confirm' => 'Delete panel',
                                                 ]) }})" />
                                </div>
                            </div>
                        @empty
                            <flux:text size="sm">
                                Nothing here. The homepage drops this half of the hero and the other half fills the width.
                            </flux:text>
                        @endforelse
                    </div>
                </flux:card>
            @endforeach
        </div>
    </div>
</div>
