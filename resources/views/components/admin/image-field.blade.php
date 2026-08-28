@props([
    'label' => 'Image',
    'description' => null,
    'photo' => null,           // pending upload (TemporaryUploadedFile)
    'galleryImage' => null,    // path chosen from the gallery
    'currentUrl' => null,      // image already saved on the record
    'picking' => false,
    'choices' => [],
    'collections' => [],
    'collection' => '',
    'model' => 'photo',
])

<div>
    <flux:input type="file" wire:model="{{ $model }}" :label="$label" accept="image/*" />

    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
        @if ($description)
            <flux:text size="sm">{{ $description }}</flux:text>
        @endif

        <button type="button" wire:click="openGalleryPicker"
                class="text-sm font-medium text-brand-600 underline-offset-2 hover:underline">
            or choose from the gallery
        </button>
    </div>

    <div wire:loading wire:target="{{ $model }}" class="mt-2 text-sm text-zinc-500">Uploading…</div>

    {{-- Whichever image is actually going to be saved --}}
    @if ($photo && $photo->isPreviewable())
        <div class="mt-3 flex items-start gap-3">
            <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="h-28 w-40 rounded-lg object-cover">
            <flux:badge size="sm" color="lime">New upload</flux:badge>
        </div>
    @elseif ($galleryImage)
        <div class="mt-3 flex items-start gap-3">
            <img src="{{ \App\Models\GalleryItem::urlFor($galleryImage) }}" alt="Chosen from gallery"
                 class="h-28 w-40 rounded-lg object-cover">
            <div class="flex flex-col items-start gap-2">
                <flux:badge size="sm" color="sky">From gallery</flux:badge>
                <flux:button size="xs" variant="ghost" type="button" wire:click="clearGalleryImage">Remove</flux:button>
            </div>
        </div>
    @elseif ($currentUrl)
        <div class="mt-3 flex items-start gap-3">
            <img src="{{ $currentUrl }}" alt="Current image" class="h-28 w-40 rounded-lg object-cover">
            <flux:badge size="sm" color="zinc">Current</flux:badge>
        </div>
    @endif

    {{-- Picker --}}
    @if ($picking)
        <div class="fixed inset-0 z-100 flex items-center justify-center bg-black/60 p-4"
             wire:click.self="closeGalleryPicker"
             @keydown.escape.window="$wire.closeGalleryPicker()">
            <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:heading size="lg">Choose from the gallery</flux:heading>
                    <flux:button size="sm" variant="ghost" icon="x-mark" type="button"
                                 wire:click="closeGalleryPicker" aria-label="Close" />
                </div>

                @if (count($collections) > 1)
                    <div class="flex flex-wrap gap-2 border-b border-zinc-200 p-4 dark:border-zinc-700">
                        <button type="button" wire:click="$set('galleryCollection', '')"
                                @class([
                                    'rounded-full px-3 py-1 text-xs font-medium transition',
                                    'bg-brand-600 text-white' => $collection === '',
                                    'bg-zinc-100 dark:bg-zinc-800' => $collection !== '',
                                ])>All</button>

                        @foreach ($collections as $name)
                            <button type="button" wire:click="$set('galleryCollection', '{{ $name }}')"
                                    @class([
                                        'rounded-full px-3 py-1 text-xs font-medium capitalize transition',
                                        'bg-brand-600 text-white' => $collection === $name,
                                        'bg-zinc-100 dark:bg-zinc-800' => $collection !== $name,
                                    ])>{{ $name }}</button>
                        @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-3 gap-3 overflow-y-auto p-4 sm:grid-cols-4 md:grid-cols-6">
                    @forelse ($choices as $item)
                        <button type="button" wire:click="chooseGalleryImage('{{ $item->image }}')"
                                title="{{ $item->title ?? $item->collection }}"
                                class="group relative aspect-square overflow-hidden rounded-lg ring-offset-2 transition hover:ring-2 hover:ring-brand-500 dark:ring-offset-zinc-900">
                            <img src="{{ $item->image_url }}" alt="{{ $item->title ?? 'Gallery image' }}"
                                 loading="lazy" class="size-full object-cover">
                            <span class="absolute inset-0 bg-brand-600/0 transition group-hover:bg-brand-600/20"></span>
                        </button>
                    @empty
                        <flux:text class="col-span-full">The gallery is empty. Upload images under Content → Gallery.</flux:text>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
