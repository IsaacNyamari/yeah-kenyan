<?php

use App\Models\User;
use App\Support\Permission;
use App\Support\UserRole;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;

/*
 * The seeder is the one place roles and permissions are defined, and it runs
 * from the migrations as well as from db:seed. Both paths depend on it being
 * safe to run again over a database that already has them.
 */

it('creates every role and permission in the enums', function () {
    (new RoleSeeder)->run();

    expect(Role::pluck('name')->all())->toEqualCanonicalizing(UserRole::names())
        ->and(PermissionModel::pluck('name')->all())->toEqualCanonicalizing(Permission::names());
});

it('gives each role exactly the permissions its enum declares', function () {
    (new RoleSeeder)->run();

    foreach (UserRole::cases() as $role) {
        expect(Role::findByName($role->value)->permissions->pluck('name')->all())
            ->toEqualCanonicalizing($role->permissionNames());
    }
});

it('can be run again without duplicating anything', function () {
    // The migrations call it, and so does db:seed, so it runs more than once
    // on any database that has been deployed to twice.
    (new RoleSeeder)->run();
    (new RoleSeeder)->run();

    expect(Role::count())->toBe(count(UserRole::cases()))
        ->and(PermissionModel::count())->toBe(count(Permission::cases()));
});

it('leaves the roles people already hold alone when it runs again', function () {
    $moderator = User::factory()->moderator()->create();

    (new RoleSeeder)->run();

    expect($moderator->fresh()->primaryRole())->toBe(UserRole::Moderator);
});

it('withdraws a permission the enum no longer grants a role', function () {
    // syncPermissions rather than givePermissionTo, so narrowing a role in the
    // enum actually takes the ability away rather than leaving it granted.
    $moderator = Role::findByName(UserRole::Moderator->value);
    $moderator->givePermissionTo(Permission::ManageSettings->value);

    (new RoleSeeder)->run();

    expect($moderator->fresh()->hasPermissionTo(Permission::ManageSettings->value))->toBeFalse();
});

it('restores a role somebody deleted by hand', function () {
    Role::findByName(UserRole::Author->value)->delete();

    (new RoleSeeder)->run();

    expect(Role::where('name', UserRole::Author->value)->exists())->toBeTrue();
});
