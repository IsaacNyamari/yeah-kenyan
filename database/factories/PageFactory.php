<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'slug' => Str::slug($title),
            'type' => Page::TYPE_SERVICE,
            'nav' => Str::title($title),
            'title' => Str::title($title),
            'heading' => fake()->sentence(),
            'cta' => 'Get Service',
            'image' => 'images/stage.jpg',
            'intro' => fake()->paragraph(),
            'sections' => [[
                'heading' => 'Why Choose Us?',
                'items' => [['label' => 'Quality', 'text' => fake()->sentence()]],
            ]],
            'footnotes' => null,
            'sort_order' => 0,
            'is_published' => true,
        ];
    }

    public function onlineClass(): static
    {
        return $this->state(['type' => Page::TYPE_CLASS, 'cta' => 'Enroll Now']);
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }
}
