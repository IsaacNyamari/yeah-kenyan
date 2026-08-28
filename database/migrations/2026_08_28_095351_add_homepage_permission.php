<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Registers the homepage permission and gives it to administrators.
 *
 * The seeder is the one definition of roles and permissions, so rolling out a
 * new ability is a matter of running it again.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RoleSeeder)->run();
    }

    public function down(): void
    {
        (new RoleSeeder)->run();
    }
};
