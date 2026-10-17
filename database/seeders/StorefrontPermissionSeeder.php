<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StorefrontPermissionSeeder extends Seeder
{
    /** Add the new grant without replacing any existing role assignments. */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('storefront.manage', 'api');
        foreach (Role::query()->where('guard_name', 'api')->whereIn('name', ['Admin', 'Manager'])->get() as $role) {
            $role->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
