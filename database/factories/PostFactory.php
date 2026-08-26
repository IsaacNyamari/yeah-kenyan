<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence();

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'author' => 'Yeah Kenyan',
            'excerpt' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'image' => 'uploads/event-images-15.jpeg',
            'is_featured' => false,
            'is_trending' => false,
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['published_at' => null]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function trending(): static
    {
        return $this->state(['is_trending' => true]);
    }
}
