<?php

namespace App\Concerns;

use App\Models\GalleryItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Testimonial;
use App\Services\ImageOptimizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * Lets an editor reuse an image already in the gallery instead of uploading
 * another copy of it.
 *
 * The chosen path is stored as-is, so the file is shared rather than
 * duplicated. That is the point of the feature, and it is also why gallery
 * deletion has to check for references before removing anything from disk —
 * see GalleryItem::isReferencedElsewhere().
 */
trait PicksGalleryImages
{
    /**
     * Path of the gallery image chosen for the record being edited.
     */
    public ?string $galleryImage = null;

    /**
     * Path already saved on the record being edited, shown when neither an
     * upload nor a gallery pick is pending.
     */
    public ?string $currentImage = null;

    public bool $pickingFromGallery = false;

    public string $galleryCollection = '';

    /**
     * @return Collection<int, GalleryItem>
     */
    #[Computed]
    public function galleryChoices(): Collection
    {
        return GalleryItem::query()
            ->when($this->galleryCollection !== '', fn ($query) => $query->where('collection', $this->galleryCollection))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->take(60)
            ->get();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function galleryCollections(): array
    {
        return GalleryItem::query()->distinct()->orderBy('collection')->pluck('collection')->all();
    }

    public function openGalleryPicker(): void
    {
        $this->pickingFromGallery = true;
    }

    public function closeGalleryPicker(): void
    {
        $this->pickingFromGallery = false;
    }

    public function chooseGalleryImage(string $path): void
    {
        // An upload and a gallery pick are alternatives, so taking one clears
        // the other rather than leaving the editor with two pending images.
        $this->galleryImage = $path;
        $this->reset('photo');
        $this->pickingFromGallery = false;

        $this->resetValidation('photo');
    }

    public function clearGalleryImage(): void
    {
        $this->galleryImage = null;
    }

    public function updatedPhoto(): void
    {
        // Uploading replaces a pick, for the same reason.
        $this->galleryImage = null;
    }

    /**
     * Stop pointing at an image, deleting the file only if nothing else needs it.
     *
     * Sharing is the whole point of picking from the gallery, so an image may
     * be in use by several records at once. Deleting on detach would break
     * every other one, and would also remove files migrated from the legacy
     * site that live under public/ rather than on the storage disk.
     */
    protected function detachImage(?string $path, ImageOptimizer $optimizer, ?Model $except = null): void
    {
        if (blank($path) || Str::startsWith($path, ['uploads/', 'images/'])) {
            return;
        }

        if ($this->imageIsSharedElsewhere($path, $except)) {
            return;
        }

        $optimizer->delete($path);
    }

    /**
     * The record being saved still holds the old path in the database at this
     * point, so it has to be excluded or every image would look shared with
     * itself and nothing would ever be cleaned up.
     */
    private function imageIsSharedElsewhere(string $path, ?Model $except = null): bool
    {
        foreach ([GalleryItem::class, Post::class, Page::class, Testimonial::class] as $model) {
            $query = $model::where('image', $path);

            if ($except instanceof $model && $except->exists) {
                $query->whereKeyNot($except->getKey());
            }

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }
}
