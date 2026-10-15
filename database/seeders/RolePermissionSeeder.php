<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'dashboard.view',
        'categories.view', 'categories.create', 'categories.update', 'categories.delete',
        'products.view', 'products.create', 'products.update', 'products.delete',
        'customers.view', 'customers.create', 'customers.update', 'customers.delete',
        'orders.view', 'orders.create', 'orders.update-status', 'orders.cancel', 'orders.manage-payment',
        'inventory.view', 'inventory.manage',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'api');
        }
        // DatabaseSeeder suppresses model events, so newly created permissions must be reloaded before assignment.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('Admin', 'api')->syncPermissions(self::PERMISSIONS);
        Role::findOrCreate('Manager', 'api')->syncPermissions(array_values(array_filter(self::PERMISSIONS, fn ($name) => ! str_ends_with($name, '.delete'))));
        Role::findOrCreate('Staff', 'api')->syncPermissions([
            'dashboard.view', 'categories.view', 'products.view', 'customers.view', 'customers.create', 'customers.update',
            'orders.view', 'orders.create', 'inventory.view',
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
