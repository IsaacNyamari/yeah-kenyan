<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles first: everything else in the application authorises against
        // them, and re-running this is the supported way to roll out a new
        // permission after adding it to the enum.
        $this->call(RoleSeeder::class);

        $this->call(SiteContentSeeder::class);
    }
}
