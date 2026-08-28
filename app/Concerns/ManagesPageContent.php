<?php

namespace App\Concerns;

use App\Exceptions\ImageProcessingException;
use App\Models\Page;
use App\Services\ImageOptimizer;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * CRUD behaviour shared by the Services and Online Classes editors.
 *
 * Both manage rows in the `pages` table and differ only by `type`, so the
 * consuming component supplies that through {@see pageType()}.
 */
trait ManagesPageContent
{
    use ConfirmsActions;
    use PicksGalleryImages;

    public ?int $editingId = null;

    public string $slug = '';

    public string $nav = '';

    public string $title = '';

    public string $heading = '';

    public string $cta = '';

    public string $intro = '';

    public bool $is_published = true;

    public ?TemporaryUploadedFile $photo = null;

    /**
     * Editable copy of the page's sections.
     *
     * @var array<int, array{heading: string, intro: string, items: array<int, array{label: string, text: string}>}>
     */
    public array $sections = [];

    abstract public function pageType(): string;

    /**
     * @return list<string>
     */
    public function confirmableActions(): array
    {
        return ['delete'];
    }

    /**
     * @return Collection<int, Page>
     */
    #[Computed]
    public function pages(): Collection
    {
        return Page::query()
            ->where('type', $this->pageType())
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nav' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:160'],
            'heading' => ['required', 'string', 'max:200'],
            'cta' => ['required', 'string', 'max:60'],
            'intro' => ['required', 'string', 'max:4000'],
            'slug' => [
                'required', 'string', 'max:160', 'regex:/^[a-z0-9-]+$/',
                'unique:pages,slug'.($this->editingId ? ",{$this->editingId}" : ''),
            ],
            'photo' => ['nullable', 'image', 'max:8192'],
            'sections' => ['array'],
            'sections.*.heading' => ['required', 'string', 'max:200'],
            'sections.*.intro' => ['nullable', 'string', 'max:1000'],
            'sections.*.items' => ['array'],
            'sections.*.items.*.label' => ['required', 'string', 'max:200'],
            'sections.*.items.*.text' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nav' => 'menu label',
            'photo' => 'hero image',
        ];
    }

    public function updatedTitle(string $value): void
    {
        // Only auto-slug while creating, so existing URLs are never broken.
        if ($this->editingId === null) {
            $this->slug = Str::slug($value);
        }
    }

    public function addSection(): void
    {
        $this->sections[] = ['heading' => '', 'intro' => '', 'items' => [['label' => '', 'text' => '']]];
    }

    public function removeSection(int $index): void
    {
        unset($this->sections[$index]);
        $this->sections = array_values($this->sections);
    }

    public function addItem(int $sectionIndex): void
    {
        $this->sections[$sectionIndex]['items'][] = ['label' => '', 'text' => ''];
    }

    public function removeItem(int $sectionIndex, int $itemIndex): void
    {
        unset($this->sections[$sectionIndex]['items'][$itemIndex]);
        $this->sections[$sectionIndex]['items'] = array_values($this->sections[$sectionIndex]['items']);
    }

    public function save(ImageOptimizer $optimizer): void
    {
        // Blank rows are an artifact of clicking "Add section"/"Add item" and
        // then not filling them in, so drop them before validating rather than
        // making the author delete each one to get past the error.
        $this->sections = $this->normaliseSections($this->cleanSections());

        $validated = $this->validate();

        $page = $this->editingId ? Page::findOrFail($this->editingId) : new Page;

        if ($this->photo instanceof TemporaryUploadedFile) {
            try {
                $image = $optimizer->store($this->photo, 'pages');
            } catch (ImageProcessingException $e) {
                $this->addError('photo', $e->getMessage());

                return;
            }

            $this->detachImage($page->image, $optimizer, $page);
            $page->image = $image;
        } elseif (filled($this->galleryImage)) {
            // Reused as-is: the file is shared with the gallery, not copied.
            $this->detachImage($page->image, $optimizer, $page);
            $page->image = $this->galleryImage;
        }

        $page->fill([
            'slug' => $validated['slug'],
            'type' => $this->pageType(),
            'nav' => $validated['nav'],
            'title' => $validated['title'],
            'heading' => $validated['heading'],
            'cta' => $validated['cta'],
            'intro' => $validated['intro'],
            'sections' => $this->cleanSections(),
            'is_published' => $this->is_published,
        ]);

        $page->sort_order ??= (int) Page::where('type', $this->pageType())->max('sort_order') + 1;

        $page->save();

        $this->resetForm();

        Flux::toast(variant: 'success', heading: 'Saved', text: 'The page was saved.');
    }

    public function edit(int $id): void
    {
        $page = Page::findOrFail($id);

        $this->editingId = $page->id;
        $this->slug = $page->slug;
        $this->nav = $page->nav;
        $this->title = $page->title;
        $this->heading = $page->heading;
        $this->cta = $page->cta;
        $this->intro = $page->intro;
        $this->is_published = $page->is_published;
        $this->photo = null;
        $this->galleryImage = null;
        $this->currentImage = $page->image;
        $this->sections = $this->normaliseSections($page->sections ?? []);

        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        $optimizer = app(ImageOptimizer::class);

        $page = Page::findOrFail($id);

        $this->detachImage($page->image, $optimizer, $page);

        $page->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        Flux::toast(variant: 'success', heading: 'Deleted', text: 'The page was removed.');
    }

    public function togglePublished(int $id): void
    {
        $page = Page::findOrFail($id);
        $page->update(['is_published' => ! $page->is_published]);

        Flux::toast(
            variant: 'success',
            text: $page->is_published ? 'Page is now live.' : 'Page is now hidden.',
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

    public function resetForm(): void
    {
        $this->reset('editingId', 'slug', 'nav', 'title', 'heading', 'intro', 'photo', 'galleryImage', 'currentImage', 'sections');
        $this->cta = $this->pageType() === Page::TYPE_CLASS ? 'Enroll Now' : 'Get Service';
        $this->is_published = true;
        $this->resetValidation();
    }

    /**
     * Reorder by swapping sort_order with the adjacent page.
     */
    private function swapWithNeighbour(int $id, int $direction): void
    {
        $pages = Page::where('type', $this->pageType())->orderBy('sort_order')->get();

        $index = $pages->search(fn (Page $page): bool => $page->id === $id);
        $target = $index + $direction;

        if ($index === false || $target < 0 || $target >= $pages->count()) {
            return;
        }

        $current = $pages[$index];
        $neighbour = $pages[$target];

        [$current->sort_order, $neighbour->sort_order] = [$neighbour->sort_order, $current->sort_order];

        $current->save();
        $neighbour->save();

        unset($this->pages);
    }

    /**
     * Drop blank rows so empty inputs never reach the public page.
     *
     * @return array<int, array<string, mixed>>
     */
    private function cleanSections(): array
    {
        $sections = [];

        foreach ($this->sections as $section) {
            $items = array_values(array_filter(
                $section['items'] ?? [],
                fn (array $item): bool => filled($item['label'] ?? null) || filled($item['text'] ?? null),
            ));

            if (blank($section['heading'] ?? null) && $items === []) {
                continue;
            }

            $sections[] = array_filter([
                'heading' => $section['heading'] ?? '',
                'intro' => $section['intro'] ?? '',
                'items' => $items,
            ], fn ($value): bool => $value !== '' && $value !== []);
        }

        return $sections;
    }

    /**
     * Guarantee every section has the keys the editor binds to.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array{heading: string, intro: string, items: array<int, array{label: string, text: string}>}>
     */
    private function normaliseSections(array $sections): array
    {
        return array_map(fn (array $section): array => [
            'heading' => $section['heading'] ?? '',
            'intro' => $section['intro'] ?? '',
            'items' => array_map(fn (array $item): array => [
                'label' => $item['label'] ?? '',
                'text' => $item['text'] ?? '',
            ], $section['items'] ?? []),
        ], $sections);
    }
}
