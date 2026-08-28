<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Gives subscribers what a mailing list needs beyond an address.
 *
 * Unsubscribing marks a row rather than deleting it. A deleted address can be
 * re-added by the public form and start receiving mail again, which is exactly
 * what the person asked not to happen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table): void {
            $table->string('name')->nullable()->after('email');
            $table->timestamp('unsubscribed_at')->nullable()->after('name')->index();
            $table->string('token', 64)->nullable()->after('unsubscribed_at');
        });

        // Every existing subscriber needs a token so the unsubscribe link in
        // their next newsletter resolves.
        DB::table('subscribers')->select('id')->orderBy('id')->chunk(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('subscribers')->where('id', $row->id)->update(['token' => Str::random(48)]);
            }
        });

        Schema::table('subscribers', function (Blueprint $table): void {
            $table->string('token', 64)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table): void {
            $table->dropUnique(['token']);
            $table->dropColumn(['name', 'unsubscribed_at', 'token']);
        });
    }
};
