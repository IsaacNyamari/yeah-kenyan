<?php

use App\Support\Permission;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Moves from three coarse abilities to one permission per dashboard area, so
 * an administrator can grant "review submissions" without also granting "edit
 * the gallery".
 *
 * The seeder creates the new permissions and re-syncs the roles. Anything left
 * over from the old naming is then dropped, because a permission row nothing
 * references is only a trap for the next person reading the roles screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RoleSeeder)->run();

        $known = Permission::names();

        $stale = DB::table('permissions')->whereNotIn('name', $known)->pluck('id');

        if ($stale->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $stale)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $stale)->delete();
            DB::table('permissions')->whereIn('id', $stale)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // The seeder is the source of truth in either direction.
        (new RoleSeeder)->run();
    }
};
