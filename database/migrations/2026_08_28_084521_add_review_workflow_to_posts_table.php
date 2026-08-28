<?php

use App\Support\PostStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Puts articles through review.
 *
 * The column defaults to draft so a row inserted without an explicit status is
 * invisible rather than live. Everything already in the table predates review
 * and was written by a trusted account, so it is backfilled as approved —
 * without that, publishing the change would empty the newsroom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('status')->default(PostStatus::Draft->value)->after('image')->index();
            $table->foreignId('submitted_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->after('submitted_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
        });

        DB::table('posts')->update(['status' => PostStatus::Approved->value]);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['status', 'reviewed_at', 'review_note']);
        });
    }
};
