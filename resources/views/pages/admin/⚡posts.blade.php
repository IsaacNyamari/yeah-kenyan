<?php

use App\Concerns\ConfirmsActions;
use App\Models\Category;
use App\Models\Post;
use App\Services\ArticleHtml;
use App\Exceptions\ImageProcessingException;
use App\Services\ImageOptimizer;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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
        return Post::with('category')->latest()->paginate(10);
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
            'photo' => [$this->editingId ? 'nullable' : 'required', 'image', 'max:8192'],
        ];
    }

    public function save(ImageOptimizer $optimizer): void
    {
        $validated = $this->validate();

        $post = $this->editingId ? Post::findOrFail($this->editingId) : new Post();

        if ($this->photo instanceof TemporaryUploadedFile) {
            try {
                $image = $optimizer->store($this->photo, 'posts');
            } catch (ImageProcessingException $e) {
                $this->addError('photo', $e->getMessage());

                return;
            }

            $optimizer->delete($post->exists && ! str_starts_with((string) $post->image, 'uploads/') ? $post->image : null);
            $post->image = $image;
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
        $post->published_at = $this->publish ? ($post->published_at ?? now()) : null;

        $post->save();

        $this->resetForm();

        Flux::toast(
            variant: 'success',
            heading: 'Saved',
            text: 'The article was saved and its image optimized.',
        );
    }

    public function edit(int $id): void
    {
        $post = Post::findOrFail($id);

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

        if (! str_starts_with((string) $post->image, 'uploads/')) {
            $optimizer->delete($post->image);
        }

        $post->delete();

        Flux::toast(variant: 'success', heading: 'Deleted', text: 'The article was removed.');
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'title', 'excerpt', 'body', 'photo', 'is_featured', 'is_trending');
        $this->author = 'Yeah Kenyan';
        $this->publish = true;
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <x-admin.confirm-modal :pending="$pendingAction" />

    <div>
        <flux:heading size="xl">News</flux:heading>
        <flux:text class="mt-1">Write and publish articles for the site newsroom.</flux:text>
    </div>

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

                    <div>
                        <flux:input type="file" wire:model="photo" label="Cover image" accept="image/*" />
                        <flux:text size="sm" class="mt-1">
                            Resized to 1600px and converted to WebP automatically. Max 8&nbsp;MB.
                        </flux:text>

                        <div wire:loading wire:target="photo" class="mt-2 text-sm text-zinc-500">
                            Uploading image...
                        </div>

                        @if ($photo && $photo->isPreviewable())
                            <img src="{{ $photo->temporaryUrl() }}" alt="Preview"
                                 class="mt-3 h-40 w-full rounded-lg object-cover">
                        @endif
                    </div>

                    <div class="space-y-2">
                        <flux:checkbox wire:model="is_featured" label="Feature on homepage" />
                        <flux:checkbox wire:model="is_trending" label="Show in trending" />
                        <flux:checkbox wire:model="publish" label="Published" />
                    </div>

                    <div class="flex gap-3">
                        <flux:button type="submit" variant="primary">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Publish' }}</span>
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
                                <h3 class="truncate font-semibold">{{ $post->title }}</h3>
                                <p class="mt-0.5 text-xs text-zinc-500">
                                    <span class="capitalize">{{ $post->category->name }}</span>
                                    &middot;
                                    {{ site_time($post->published_at)?->format('M d, Y') ?? 'Draft' }}
                                    @if ($post->is_featured) &middot; Featured @endif
                                </p>
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
