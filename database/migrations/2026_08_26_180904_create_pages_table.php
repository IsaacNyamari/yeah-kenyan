<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type')->index();          // service | class
            $table->string('nav');                    // label used in menus
            $table->string('title');
            $table->string('heading');
            $table->string('cta')->default('Get Service');
            $table->string('image')->nullable();
            $table->text('intro');
            $table->json('sections')->nullable();
            $table->json('footnotes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
