<?php

use App\Concerns\ConfirmsActions;
use App\Concerns\PicksGalleryImages;
use App\Models\Category;
use App\Models\Post;
use App\Support\PostStatus;
use App\Services\ArticleHtml;
use App\Exceptions\ImageProcessingException;
use App\Services\ImageOptimizer;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Manage News')] class extends Component {
    use ConfirmsActions;
    use PicksGalleryImages;
    use WithFileUploads;
    use WithPagination;

    public ?int $editingId = null;

    public string $title = '';

    public string $author = 'Yeah Kenyan';

    public ?int $category_id = null;

    public string $excerpt = '';

    public string $body = '';

    public bool $is_featured = false;

    public bool $is_trending = false;

    public bool $publish = true;

    public ?TemporaryUploadedFile $photo = null;

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        return Post::with('category', 'reviewer')
            // An author sees their own work only; showing everyone's would let
            // them edit articles they did not write.
            ->unless($this->canModerate, fn ($query) => $query->where('submitted_by', auth()->id()))
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function canModerate(): bool
    {
        return Gate::allows('moderate-content');
    }

    #[Computed]
    public function canPost(): bool
    {
        return Gate::allows('post-content');
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::orderBy('name')->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            // 8 MB ceiling: phone photos routinely exceed the PHP default.
            'photo' => [$this->editingId || filled($this->galleryImage) ? 'nullable' : 'required', 'image', 'max:8192'],
        ];
    }

    public function save(ImageOptimizer $optimizer): void
    {
        abort_unless($this->canPost, 403, 'Posting is currently closed.');

        $validated = $this->validate();

        $post = $this->editingId ? Post::findOrFail($this->editingId) : new Post();

        abort_unless($this->mayEdit($post), 403);

        if ($this->photo instanceof TemporaryUploadedFile) {
            try {
                $image = $optimizer->store($this->photo, 'posts');
            } catch (ImageProcessingException $e) {
                $this->addError('photo', $e->getMessage());

                return;
            }

            $this->detachImage($post->image, $optimizer, $post);
            $post->image = $image;
        } elseif (filled($this->galleryImage)) {
            // Reused as-is: the file is shared with the gallery, not copied.
            $this->detachImage($post->image, $optimizer, $post);
            $post->image = $this->galleryImage;
        }

        $post->fill([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'category_id' => $validated['category_id'],
            'excerpt' => $validated['excerpt'] ?: null,
            // Rendered unescaped on the public page, so it is sanitized on the way in.
            'body' => app(ArticleHtml::class)->sanitize($validated['body']),
            'is_featured' => $this->is_featured,
            'is_trending' => $this->is_trending,
        ]);

        $post->slug ??= Str::slug($validated['title']).'-'.Str::lower(Str::random(6));
        $post->submitted_by ??= auth()->id();

        if ($this->canModerate) {
            // Someone who could approve this in the queue anyway; making them
            // round-trip through it would be ceremony, not review.
            $post->status = PostStatus::Approved;
            $post->reviewed_by = auth()->id();
            $post->reviewed_at = now();
            $post->review_note = null;
            $post->published_at = $this->publish ? ($post->published_at ?? now()) : null;
        } else {
            // An author's article goes to the queue, and stays off the site
            // until a moderator approves it.
            $post->status = PostStatus::Pending;
            $post->reviewed_by = null;
            $post->reviewed_at = null;
            $post->published_at = null;
        }

        $post->save();

        $this->resetForm();

        Flux::toast(
            variant: 'success',
            heading: 'Saved',
            text: $this->canModerate
                ? 'The article was saved and its image optimized.'
                : 'The article was sent for review. A moderator will approve it before it appears on the site.',
        );
    }

    public function edit(int $id): void
    {
        $post = Post::findOrFail($id);

        abort_unless($this->mayEdit($post), 403);

        $this->editingId = $post->id;
        $this->title = $post->title;
        $this->author = $post->author;
        $this->category_id = $post->category_id;
        $this->excerpt = (string) $post->excerpt;
        $this->body = $post->body;
        $this->is_featured = $post->is_featured;
        $this->is_trending = $post->is_trending;
        $this->publish = $post->published_at !== null;
        $this->photo = null;
        $this->galleryImage = null;
        $this->currentImage = $post->image;
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

        $post = Post::findOrFail($id);

        abort_unless($this->mayEdit($post), 403);

        $this->detachImage($post->image, $optimizer, $post);

        $post->delete();

        Flux::toast(variant: 'success', heading: 'Deleted', text: 'The article was removed.');
    }

    /**
     * Moderators and administrators may work on anything. An author may only
     * touch their own article, and only while it is still theirs to change —
     * once it is queued or live, editing it would sidestep the review that
     * approved it.
     */
    private function mayEdit(Post $post): bool
    {
        if ($this->canModerate) {
            return true;
        }

        if (! $post->exists) {
            return true;
        }

        return $post->submitted_by === auth()->id()
            && $post->status->isEditableByAuthor();
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'title', 'excerpt', 'body', 'photo', 'galleryImage', 'currentImage', 'is_featured', 'is_trending');
        $this->author = 'Yeah Kenyan';
        $this->publish = true;
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <x-admin.confirm-modal :pending="$pendingAction" />

    <div>
        <flux:heading size="xl">News</flux:heading>
        <flux:text class="mt-1">
            @if ($this->canModerate)
                Write and publish articles for the site newsroom.
            @else
                Write articles for the site newsroom. A moderator reviews each one before it goes live.
            @endif
        </flux:text>
    </div>

    @unless ($this->canPost)
        <flux:callout variant="warning">
            <flux:callout.heading>Posting is paused</flux:callout.heading>
            <flux:callout.text>
                An administrator has switched off posting for the moment. You can still read what you have
                written, but saving is disabled until it is switched back on.
            </flux:callout.text>
        </flux:callout>
    @endunless

    <div>
        <div class="grid gap-10 lg:grid-cols-5">

            {{-- Editor --}}
            <div class="lg:col-span-2">
                <x-site.section-heading :title="$editingId ? 'Edit Article' : 'New Article'" />

                <form wire:submit="save" class="space-y-5">
                    <flux:input wire:model="title" label="Title" required />

                    <flux:select wire:model="category_id" label="Category" placeholder="Choose a category" required>
                        @foreach ($this->categories as $category)
                            <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="author" label="Author" required />

                    <flux:textarea wire:model="excerpt" label="Excerpt" rows="3"
                                   placeholder="Short summary shown on cards" />

                    <flux:textarea wire:model="body" label="Body" rows="10" required />

                    <x-admin.image-field
                        label="Cover image"
                        description="Resized to 1600px and converted to WebP. Max 8 MB."
                        :photo="$photo"
                        :gallery-image="$galleryImage"
                        :current-url="\App\Models\GalleryItem::urlFor($currentImage)"
                        :picking="$pickingFromGallery"
                        :choices="$this->galleryChoices"
                        :collections="$this->galleryCollections"
                        :collection="$galleryCollection" />

                    <div class="space-y-2">
                        <flux:checkbox wire:model="is_featured" label="Feature on homepage" />
                        <flux:checkbox wire:model="is_trending" label="Show in trending" />
                        @if ($this->canModerate)
                            <flux:checkbox wire:model="publish" label="Published" />
                        @endif
                    </div>

                    <div class="flex gap-3">
                        <flux:button type="submit" variant="primary" :disabled="! $this->canPost">
                            <span wire:loading.remove wire:target="save">
                                @if ($this->canModerate)
                                    {{ $editingId ? 'Update' : 'Publish' }}
                                @else
                                    {{ $editingId ? 'Resubmit for review' : 'Send for review' }}
                                @endif
                            </span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </flux:button>

                        @if ($editingId)
                            <flux:button type="button" wire:click="resetForm" variant="ghost">Cancel</flux:button>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Listing --}}
            <div class="lg:col-span-3">
                <x-site.section-heading title="All Articles" />

                <div class="space-y-3">
                    @forelse ($this->posts as $post)
                        <div class="flex items-center gap-4 rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                            @if ($post->image)
                                <img src="{{ $post->image_url }}" alt="{{ $post->title }}"
                                     class="size-16 shrink-0 rounded object-cover">
                            @endif

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="truncate font-semibold">{{ $post->title }}</h3>
                                    <flux:badge size="sm" :color="$post->status->badgeColor()">
                                        {{ $post->status->label() }}
                                    </flux:badge>
                                </div>
                                <p class="mt-0.5 text-xs text-zinc-500">
                                    <span class="capitalize">{{ $post->category->name }}</span>
                                    &middot;
                                    {{ site_time($post->published_at)?->format('M d, Y') ?? 'Not published' }}
                                    @if ($post->is_featured) &middot; Featured @endif
                                </p>
                                @if ($post->status === App\Support\PostStatus::Rejected && filled($post->review_note))
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                        Sent back: {{ $post->review_note }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex shrink-0 gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="edit({{ $post->id }})">Edit</flux:button>
                                <flux:button size="sm" variant="danger"
                                             wire:click="confirmAction('delete', {{ $post->id }}, {{ Js::from([
                                                 'heading' => 'Delete this article?',
                                                 'text' => Str::limit($post->title, 80).' will be permanently removed, along with its cover image.',
                                                 'confirm' => 'Delete article',
                                             ]) }})">
                                    Delete
                                </flux:button>
                            </div>
                        </div>
                    @empty
                        <p class="text-zinc-500">No articles yet.</p>
                    @endforelse
                </div>

                <div class="mt-6">{{ $this->posts->links() }}</div>
            </div>
        </div>
    </div>
</div>
