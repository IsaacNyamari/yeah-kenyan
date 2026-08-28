<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per intended recipient of an issue.
 *
 * Sending happens in chunks across several requests, because shared hosting
 * has no queue worker and one long request would time out. The unique pair is
 * what makes that safe: a retried or overlapping chunk cannot mail the same
 * person an issue twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_sends', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('newsletter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->string('failure')->nullable();
            $table->timestamps();

            $table->unique(['newsletter_id', 'subscriber_id']);
            $table->index(['newsletter_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_sends');
    }
};
