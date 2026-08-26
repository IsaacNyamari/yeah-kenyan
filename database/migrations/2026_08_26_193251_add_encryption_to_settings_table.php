<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Credentials (mail password, service-account keys) are stored
            // encrypted; this flags which rows need decrypting on read.
            $table->boolean('is_encrypted')->default(false)->after('value');
        });

        // Service-account JSON runs to a few KB, past the TEXT comfort zone
        // once encrypted.
        Schema::table('settings', function (Blueprint $table) {
            $table->longText('value')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('is_encrypted');
        });
    }
};
