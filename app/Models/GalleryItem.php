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
}
