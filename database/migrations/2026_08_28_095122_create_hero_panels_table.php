<?php

use App\Support\HeroPanelKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the homepage hero out of config and into the database.
 *
 * The rotating banner and the tiles beside it share one table and differ only
 * by kind, the way service and class pages share the pages table.
 *
 * Seeded from the config values it replaces, so the homepage looks exactly the
 * same the moment this runs. Without that, deploying would empty the hero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_panels', function (Blueprint $table): void {
            $table->id();
            $table->string('kind')->index();
            $table->string('badge');
            $table->string('text', 500);
            $table->string('image');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        /*
         * The content this replaces, written out rather than read from config.
         * A migration runs against whatever the code looked like at the time,
         * and the config block it came from is deleted in the same change — so
         * reading it here would seed nothing on a fresh install.
         */
        $seed = [
            HeroPanelKind::Slide->value => [
                ['badge' => 'Branding', 'image' => 'images/branding1.jpg', 'text' => 'We create messages that resonate with your target audience'],
                ['badge' => 'Streaming', 'image' => 'images/team1.jpg', 'text' => 'We provide seamless streaming solutions that engage and connect your audience'],
                ['badge' => 'Video Shoot', 'image' => 'images/drone.jpg', 'text' => 'We capture high-quality video shoots that tell your story with impact'],
            ],
            HeroPanelKind::Tile->value => [
                ['badge' => 'Expertise', 'image' => 'images/team2.jpg', 'text' => 'Experienced experts'],
                ['badge' => 'Unbeatable', 'image' => 'images/streaming1.jpg', 'text' => 'We deliver unbeatable video shoots that bring your vision to life'],
                ['badge' => 'Sound System', 'image' => 'images/soundsystem.jpg', 'text' => 'Powerful sound systems that elevate your events with clear, crisp audio'],
                ['badge' => 'No Hidden Costs', 'image' => 'images/hiddencosts.jpg', 'text' => 'Just quality services you can trust'],
            ],
        ];

        $rows = [];
        $now = now();

        foreach ($seed as $kind => $panels) {
            foreach ($panels as $index => $panel) {
                $rows[] = [
                    'kind' => $kind,
                    'badge' => $panel['badge'],
                    'text' => $panel['text'],
                    'image' => $panel['image'],
                    'sort_order' => $index,
                    'is_published' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('hero_panels')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_panels');
    }
};
