<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable wrappers for a newsletter: the masthead, colours and footer that
 * stay the same from issue to issue, with a placeholder where the writing goes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->longText('html');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_templates');
    }
};
