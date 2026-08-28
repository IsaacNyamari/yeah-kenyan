<?php

namespace App\Models;

use App\Concerns\ResolvesImageUrl;
use Database\Factories\GalleryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    /** @use HasFactory<GalleryItemFactory> */
    use HasFactory;

    use ResolvesImageUrl;

    protected $fillable = ['title', 'image', 'collection', 'sort_order'];

    /**
     * Whether another record points at the same file.
     *
     * Editors may attach a gallery image to a post, page or testimonial rather
     * than uploading a second copy, so the file outlives the gallery row that
     * introduced it. Deleting it from disk regardless would silently break
     * whatever else was using it.
     */
    public function isReferencedElsewhere(): bool
    {
        return Post::where('image', $this->image)->exists()
            || Page::where('image', $this->image)->exists()
            || Testimonial::where('image', $this->image)->exists();
    }
}
