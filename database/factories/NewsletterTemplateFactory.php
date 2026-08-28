<?php

namespace Database\Factories;

use App\Models\NewsletterTemplate;
use App\Services\NewsletterRenderer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterTemplate>
 */
class NewsletterTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'html' => NewsletterRenderer::STARTER_HTML,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
