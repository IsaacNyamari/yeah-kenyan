<?php

use App\Concerns\ConfirmsActions;
use App\Models\GalleryItem;
use App\Services\ImageOptimizer;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Manage Gallery')] class extends Component {
    use ConfirmsActions;
    use WithFileUploads;
    use WithPagination;

    /**
     * @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile>
     */
    public array $photos = [];

    public string $collection = 'events';

    public string $title = '';

    /**
     * @return LengthAwarePaginator<int, GalleryItem>
     */
    #[Computed]
    public function items(): LengthAwarePaginator
    {
        return GalleryItem::orderBy('sort_order')->orderByDesc('id')->paginate(24);
    }

    /**
     * Named uploadImages() rather than upload(): $wire.upload() is Livewire's
     * own file-upload JS API, so a component action called upload() gets
     * shadowed by it and wire:submit calls the uploader with no arguments.
     */
    public function uploadImages(ImageOptimizer $optimizer): void
    {
        $this->validate([
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['image', 'max:8192'],
            'collection' => ['required', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $sort = (int) GalleryItem::max('sort_order');

        foreach ($this->photos as $photo) {
            GalleryItem::create([
                'image' => $optimizer->store($photo, 'gallery'),
                'collection' => $this->collection,
                'title' => $this->title ?: null,
                'sort_order' => ++$sort,
            ]);
        }

        $count = count($this->photos);

        $this->reset('photos', 'title');

        Flux::toast(
            variant: 'success',
            heading: 'Uploaded',
            text: "{$count} image(s) optimized and added to the gallery.",
        );
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
        $optimizer = app(ImageOptimizer::class);

        $item = GalleryItem::findOrFail($id);

        if (! str_starts_with($item->image, 'uploads/')) {
            $optimizer->delete($item->image);
        }

        $item->delete();

        Flux::toast(variant: 'success', heading: 'Deleted', text: 'Image removed from the gallery.');
    }
}; ?>

<div class="space-y-6">
    <x-admin.confirm-modal :pending="$pendingAction" />

    <div>
        <flux:heading size="xl">Gallery</flux:heading>
        <flux:text class="mt-1">Upload event photos. Every image is optimized on upload.</flux:text>
    </div>

    <div>

        <x-site.section-heading title="Upload Images" />

        <form wire:submit="uploadImages" class="mb-12 grid gap-5 rounded-lg border border-zinc-200 p-6 sm:grid-cols-3 dark:border-zinc-800">
            <div class="sm:col-span-3">
                <flux:input type="file" wire:model="photos" label="Images" accept="image/*" multiple />
                <flux:text size="sm" class="mt-1">
                    Each image is resized to 1600px and converted to WebP before it is stored. Max 8&nbsp;MB each, 20 at a time.
                </flux:text>
                <div wire:loading wire:target="photos" class="mt-2 text-sm text-zinc-500">Uploading...</div>
            </div>

            <flux:input wire:model="collection" label="Collection" placeholder="events" required />
            <flux:input wire:model="title" label="Caption (optional)" placeholder="Nairobi corporate gala" />

            <div class="flex items-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    <span wire:loading.remove wire:target="uploadImages">Upload</span>
                    <span wire:loading wire:target="uploadImages">Optimizing...</span>
                </flux:button>
            </div>

            @if ($photos)
                <div class="grid grid-cols-4 gap-3 sm:col-span-3 sm:grid-cols-8">
                    @foreach ($photos as $photo)
                        @continue(! $photo->isPreviewable())
                        <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="h-20 w-full rounded object-cover">
                    @endforeach
                </div>
            @endif
        </form>

        <x-site.section-heading title="Gallery Images" />

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
            @forelse ($this->items as $item)
                <div class="group relative overflow-hidden rounded-lg">
                    <img src="{{ $item->image_url }}" alt="{{ $item->title ?? 'Gallery image' }}" loading="lazy"
                         class="h-32 w-full object-cover">
                    <div class="absolute inset-0 flex items-center justify-center bg-black/60 opacity-0 transition group-hover:opacity-100">
                        <flux:button size="sm" variant="danger"
                                     wire:click="confirmAction('delete', {{ $item->id }}, {{ Js::from([
                                         'heading' => 'Remove this image?',
                                         'text' => 'The image will be deleted from the gallery and from disk. This cannot be undone.',
                                         'confirm' => 'Remove image',
                                     ]) }})">
                            Delete
                        </flux:button>
                    </div>
                    <span class="absolute top-1 left-1 rounded bg-black/70 px-1.5 py-0.5 text-[10px] text-white capitalize">
                        {{ $item->collection }}
                    </span>
                </div>
            @empty
                <p class="col-span-full text-zinc-500">No gallery images yet.</p>
            @endforelse
        </div>

        <div class="mt-8">{{ $this->items->links() }}</div>
    </div>
</div>
