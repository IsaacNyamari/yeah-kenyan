<?php

use App\Models\NewsletterTemplate;
use App\Services\NewsletterRenderer;
use Illuminate\Database\Migrations\Migration;

/**
 * Gives the newsletter screen something to start from.
 *
 * Only when the table is empty: re-running this must not overwrite a design
 * somebody has since edited.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (NewsletterTemplate::exists()) {
            return;
        }

        NewsletterTemplate::create([
            'name' => 'Standard',
            'description' => 'Masthead, body and footer. A starting point — edit it or add your own.',
            'html' => NewsletterRenderer::STARTER_HTML,
            'is_default' => true,
        ]);
    }

    public function down(): void
    {
        NewsletterTemplate::where('name', 'Standard')->where('is_default', true)->delete();
    }
};
