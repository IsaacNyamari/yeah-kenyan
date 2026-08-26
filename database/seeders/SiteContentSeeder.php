<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\GalleryItem;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the content migrated from the legacy PHP site: the news taxonomy,
 * the team, client testimonials, and the event gallery.
 *
 * Idempotent — safe to re-run after adding more images to public/uploads.
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedTeam();
        $this->seedTestimonials();
        $this->seedGallery();
    }

    private function seedCategories(): void
    {
        $categories = [
            'Politics', 'Sports', 'Celebrities', 'Counties', 'Economy',
            'Entertainment', 'International News', 'Latest News',
            'Local News', 'Nature', 'Podcasts', 'Sermon',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }

    private function seedTeam(): void
    {
        $team = [
            [
                'name' => 'Francis Cleanheart',
                'role' => 'Director',
                'photo' => 'uploads/francis-cleanheart.jpg',
                'bio' => 'Francis is the visionary leader of our team, overseeing all operations and guiding our mission. His strategic thinking and passion for excellence have been key in our growth and success.',
            ],
            [
                'name' => 'Isaac Nyamari',
                'role' => 'Website Developer & UI/UX Designer',
                'photo' => 'uploads/isaac-nyamari.jpg',
                'bio' => 'Isaac is the backbone of our online presence, ensuring that our website is user-friendly, efficient, and aesthetically pleasing. He specializes in creating seamless user experiences.',
            ],
            [
                'name' => 'Brian Munywoki Isai (Essir)',
                'role' => 'Photographer & Drone Pilot',
                'photo' => 'uploads/brian-munywoki-isai.jpg',
                'bio' => 'Brian captures the magic through his lens, whether it is a beautiful landscape, a corporate event, or aerial shots with his drone. His work elevates the visual story we tell.',
            ],
            [
                'name' => 'Nephat Kiiru',
                'role' => 'Sales & Marketer',
                'photo' => 'uploads/nephat-kiiru.jpeg',
                'bio' => 'Nephat is responsible for developing and executing marketing strategies. His expertise in sales ensures we engage with our clients effectively and grow our reach.',
            ],
            [
                'name' => 'Collin Mboya',
                'role' => 'Videographer & Photo Editor',
                'photo' => 'uploads/collin-mboya.jpg',
                'bio' => 'Collin brings our visual content to life, ensuring high-quality video production and editing. His attention to detail in every shot makes our content visually compelling.',
            ],
            [
                'name' => 'Bonface L.',
                'role' => 'D.O.P & Live Streamer',
                'photo' => 'uploads/bonface-l.jpg',
                'bio' => 'Bonface brings our live streams to life with his skills as a Director of Photography. He ensures every live event is streamed in high quality, reaching audiences worldwide.',
            ],
            [
                'name' => "Douglas Lang'at",
                'role' => 'Photographer & Videographer',
                'photo' => 'uploads/douglas-langat.jpg',
                'bio' => 'Douglas specializes in capturing stunning moments both in photography and videography. His creativity in framing shots helps to convey the story visually.',
            ],
        ];

        foreach ($team as $index => $member) {
            TeamMember::updateOrCreate(
                ['name' => $member['name']],
                [...$member, 'sort_order' => $index],
            );
        }
    }

    private function seedTestimonials(): void
    {
        // Roles describe the kind of work each client engaged us for; edit them
        // (and add portraits) from the CMS.
        $testimonials = [
            ['quote' => 'This service was amazing! It exceeded all my expectations.', 'client' => 'Church Events', 'role' => 'Event Production'],
            ['quote' => 'Fantastic experience! I highly recommend them to everyone.', 'client' => 'GoK Programmes & Projects', 'role' => 'Government Projects'],
            ['quote' => 'A top-notch service that provided great value for money.', 'client' => 'Real Estate', 'role' => 'Property Media'],
            ['quote' => 'Professional and reliable! Their work in farming documentaries is outstanding.', 'client' => 'Private Farming Documentaries', 'role' => 'Documentary Production'],
        ];

        foreach ($testimonials as $index => $testimonial) {
            Testimonial::updateOrCreate(
                ['client' => $testimonial['client']],
                [...$testimonial, 'sort_order' => $index],
            );
        }
    }

    /**
     * Register every event photo already sitting in public/uploads.
     */
    private function seedGallery(): void
    {
        $files = glob(public_path('uploads/{wedding-*,event-images-*}.{jpg,jpeg,png,webp}'), GLOB_BRACE) ?: [];

        // wedding-2 before wedding-10, rather than lexicographically.
        natsort($files);

        foreach (array_values($files) as $index => $file) {
            $name = basename($file);

            GalleryItem::updateOrCreate(
                ['image' => 'uploads/'.$name],
                [
                    'collection' => str_starts_with($name, 'wedding') ? 'weddings' : 'events',
                    'sort_order' => $index,
                ],
            );
        }
    }
}
