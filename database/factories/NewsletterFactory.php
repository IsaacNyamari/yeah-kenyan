<?php

namespace Database\Factories;

use App\Models\Newsletter;
use App\Models\NewsletterTemplate;
use App\Support\NewsletterStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Newsletter>
 */
class NewsletterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'newsletter_template_id' => NewsletterTemplate::factory(),
            'subject' => fake()->sentence(),
            'preheader' => fake()->sentence(),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'status' => NewsletterStatus::Draft,
        ];
    }

    public function sending(): static
    {
        return $this->state(['status' => NewsletterStatus::Sending]);
    }

    public function sent(): static
    {
        return $this->state(['status' => NewsletterStatus::Sent, 'sent_at' => now()]);
    }
}
