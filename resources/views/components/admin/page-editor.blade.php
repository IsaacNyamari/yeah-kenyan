@props([
    'pages',
    'sections' => [],
    'slug' => '',
    'editingId' => null,
    'label' => 'Page',
    'photo' => null,
    'galleryImage' => null,
    'currentImage' => null,
    'pickingFromGallery' => false,
    'choices' => [],
    'collections' => [],
    'galleryCollection' => '',
])

<div class="grid gap-8 xl:grid-cols-5">

    {{-- Editor --}}
    <div class="xl:col-span-2">
        <flux:card>
            <flux:heading size="lg">{{ $editingId ? 'Edit '.$label : 'New '.$label }}</flux:heading>

            <form wire:submit="save" class="mt-6 space-y-5">
                <flux:input wire:model.blur="title" label="Page title" required />
                <flux:input wire:model="nav" label="Menu label" description="Shown in the site navigation" required />

                <flux:input wire:model="slug" label="URL slug" required>
                    <x-slot:description>
                        Lives at <span class="font-mono">/{{ $slug ?: 'your-slug' }}</span> — changing this breaks existing links.
                    </x-slot:description>
                </flux:input>

                <flux:input wire:model="heading" label="Main heading" required />
                <flux:textarea wire:model="intro" label="Intro paragraph" rows="5" required />
                <flux:input wire:model="cta" label="Button label" required />

                <x-admin.image-field
                    label="Hero image"
                    description="Resized to 1600px and converted to WebP on upload."
                    :photo="$photo"
                    :gallery-image="$galleryImage"
                    :current-url="\App\Models\GalleryItem::urlFor($currentImage)"
                    :picking="$pickingFromGallery"
                    :choices="$choices"
                    :collections="$collections"
                    :collection="$galleryCollection" />

                <flux:checkbox wire:model="is_published" label="Published" />

                <flux:separator />

                {{-- Sections --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">Content sections</flux:heading>
                        <flux:button size="sm" variant="ghost" type="button" wire:click="addSection" icon="plus">
                            Add section
                        </flux:button>
                    </div>

                    @forelse ($sections as $sectionIndex => $section)
                        <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex items-start gap-2">
                                <div class="flex-1">
                                    <flux:input wire:model="sections.{{ $sectionIndex }}.heading"
                                                placeholder="Section heading" size="sm" />
                                </div>
                                <flux:button size="sm" variant="subtle" type="button" icon="trash"
                                             wire:click="removeSection({{ $sectionIndex }})"
                                             aria-label="Remove section" />
                            </div>

                            <flux:textarea wire:model="sections.{{ $sectionIndex }}.intro"
                                           placeholder="Optional intro for this section" rows="2" />

                            <div class="space-y-2 border-s-2 border-brand-500 ps-3">
                                @foreach ($section['items'] as $itemIndex => $item)
                                    <div class="flex items-start gap-2">
                                        <div class="flex-1 space-y-1.5">
                                            <flux:input wire:model="sections.{{ $sectionIndex }}.items.{{ $itemIndex }}.label"
                                                        placeholder="Item title" size="sm" />
                                            <flux:textarea wire:model="sections.{{ $sectionIndex }}.items.{{ $itemIndex }}.text"
                                                           placeholder="Item description" rows="2" />
                                        </div>
                                        <flux:button size="sm" variant="subtle" type="button" icon="x-mark"
                                                     wire:click="removeItem({{ $sectionIndex }}, {{ $itemIndex }})"
                                                     aria-label="Remove item" />
                                    </div>
                                @endforeach

                                <flux:button size="xs" variant="ghost" type="button" icon="plus"
                                             wire:click="addItem({{ $sectionIndex }})">
                                    Add item
                                </flux:button>
                            </div>
                        </div>
                    @empty
                        <flux:text size="sm">No sections yet — add one to build out the page.</flux:text>
                    @endforelse
                </div>

                <flux:separator />

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }}</span>
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
            <flux:heading size="lg">All {{ Str::plural($label) }} ({{ $pages->count() }})</flux:heading>

            <div class="mt-6 space-y-3">
                @forelse ($pages as $page)
                    <div @class([
                        'flex items-center gap-4 rounded-lg border p-3 transition',
                        'border-brand-500 bg-brand-50/50 dark:bg-brand-900/10' => $editingId === $page->id,
                        'border-zinc-200 dark:border-zinc-700' => $editingId !== $page->id,
                    ])>
                        @if ($page->image)
                            <img src="{{ $page->image_url }}" alt="" class="size-14 shrink-0 rounded object-cover">
                        @else
                            <div class="flex size-14 shrink-0 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon.photo class="size-5 text-zinc-400" />
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="truncate font-semibold">{{ $page->nav }}</h3>

                                @unless ($page->is_published)
                                    <flux:badge size="sm" color="zinc">Hidden</flux:badge>
                                @endunless
                            </div>
                            <p class="mt-0.5 truncate font-mono text-xs text-zinc-500">/{{ $page->slug }}</p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <flux:button size="sm" variant="subtle" icon="chevron-up"
                                         wire:click="moveUp({{ $page->id }})" aria-label="Move up" />
                            <flux:button size="sm" variant="subtle" icon="chevron-down"
                                         wire:click="moveDown({{ $page->id }})" aria-label="Move down" />
                            <flux:button size="sm" variant="subtle"
                                         :icon="$page->is_published ? 'eye' : 'eye-slash'"
                                         wire:click="togglePublished({{ $page->id }})"
                                         aria-label="Toggle published" />
                            <flux:button size="sm" variant="ghost" wire:click="edit({{ $page->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="danger" icon="trash"
                                         wire:click="confirmAction('delete', {{ $page->id }}, {{ Js::from([
                                             'heading' => 'Delete this page?',
                                             'text' => $page->nav.' will be removed from the site and its menu. This cannot be undone.',
                                             'confirm' => 'Delete page',
                                         ]) }})"
                                         aria-label="Delete" />
                        </div>
                    </div>
                @empty
                    <flux:text>No {{ Str::lower(Str::plural($label)) }} yet.</flux:text>
                @endforelse
            </div>
        </flux:card>
    </div>
</div>
