<?php

namespace Database\Seeders;

use App\Support\Permission;
use App\Support\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Brings the roles and permissions in the database in line with the enums.
 *
 * Written to be safe to re-run: adding a case to either enum and running this
 * again is the supported way to roll a new ability out.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web')
                ->syncPermissions($role->permissionNames());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
