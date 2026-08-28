<?php

namespace Database\Factories;

use App\Models\HeroPanel;
use App\Support\HeroPanelKind;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeroPanel>
 */
class HeroPanelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => HeroPanelKind::Slide,
            'badge' => fake()->word(),
            'text' => fake()->sentence(),
            'image' => 'images/branding1.jpg',
            'sort_order' => 0,
            'is_published' => true,
        ];
    }

    public function tile(): static
    {
        return $this->state(['kind' => HeroPanelKind::Tile]);
    }

    public function hidden(): static
    {
        return $this->state(['is_published' => false]);
    }
}
