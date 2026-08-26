<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            // Carousel slides show a portrait, then the client and their rank,
            // then the quote itself.
            $table->string('image')->nullable()->after('client');
            $table->string('role')->nullable()->after('client');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['image', 'role']);
        });
    }
};
