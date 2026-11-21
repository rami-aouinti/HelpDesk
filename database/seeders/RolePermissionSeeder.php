<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'tickets.view',
            'tickets.view.all',
            'tickets.create',
            'tickets.update',
            'tickets.manage',
            'users.view',
            'users.create',
            'users.update',
            'users.deactivate',
            'teams.view',
            'teams.create',
            'teams.update',
            'teams.delete',
            'projects.view',
            'projects.view.all',
            'projects.create',
            'projects.update',
            'projects.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $agent = Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $admin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        $agent->syncPermissions([
            'tickets.view',
            'tickets.update',
            'projects.view',
        ]);
        $customer->syncPermissions([
            'tickets.view',
            'tickets.create',
            'projects.view',
        ]);
    }
}
