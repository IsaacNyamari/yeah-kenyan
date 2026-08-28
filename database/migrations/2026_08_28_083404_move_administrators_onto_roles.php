<?php

use App\Support\UserRole;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Replaces the is_admin flag with a role.
 *
 * The roles are created here rather than in a seeder alone because the whole
 * application authorises against them, so every environment — a fresh test
 * database included — needs them present the moment migrations finish.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RoleSeeder)->run();

        $administrators = DB::table('users')->where('is_admin', true)->pluck('id');

        if ($administrators->isNotEmpty()) {
            $adminRole = Role::findByName(UserRole::Admin->value, 'web');

            DB::table('model_has_roles')->insertOrIgnore(
                $administrators->map(fn (int $id): array => [
                    'role_id' => $adminRole->getKey(),
                    'model_type' => 'App\Models\User',
                    'model_id' => $id,
                ])->all(),
            );
        }

        // Everyone else keeps CMS access, so they become authors rather than
        // losing the dashboard they already use.
        $authorRole = Role::findByName(UserRole::Author->value, 'web');

        $unassigned = DB::table('users')
            ->whereNotIn('id', DB::table('model_has_roles')->where('model_type', 'App\Models\User')->pluck('model_id'))
            ->pluck('id');

        if ($unassigned->isNotEmpty()) {
            DB::table('model_has_roles')->insertOrIgnore(
                $unassigned->map(fn (int $id): array => [
                    'role_id' => $authorRole->getKey(),
                    'model_type' => 'App\Models\User',
                    'model_id' => $id,
                ])->all(),
            );
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->after('email_verified_at');
        });

        $adminIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', UserRole::Admin->value)
            ->where('model_has_roles.model_type', 'App\Models\User')
            ->pluck('model_has_roles.model_id');

        if ($adminIds->isNotEmpty()) {
            DB::table('users')->whereIn('id', $adminIds)->update(['is_admin' => true]);
        }
    }
};
