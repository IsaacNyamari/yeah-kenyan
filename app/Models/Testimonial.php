<?php

namespace App\Models;

use App\Concerns\ResolvesImageUrl;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    use ResolvesImageUrl;

    protected $fillable = ['quote', 'client', 'role', 'image', 'sort_order'];
}
