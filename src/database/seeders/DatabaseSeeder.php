<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

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
            'tickets.view.all',
            'tickets.update',
        ]);
        $customer->syncPermissions([
            'tickets.view',
            'tickets.create',
        ]);

        $password = env('HELPDESK_DEMO_PASSWORD', 'password');

        $users = [
            ['name' => 'HelpDesk Administrator', 'email' => env('HELPDESK_ADMIN_EMAIL', 'admin@helpdesk.test'), 'role' => $admin],
            ['name' => 'Support Agent', 'email' => env('HELPDESK_AGENT_EMAIL', 'agent@helpdesk.test'), 'role' => $agent],
            ['name' => 'Demo Customer', 'email' => env('HELPDESK_CUSTOMER_EMAIL', 'customer@helpdesk.test'), 'role' => $customer],
        ];

        foreach ($users as $definition) {
            $user = User::firstOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'password' => $password,
                    'email_verified_at' => now(),
                ],
            );

            $user->assignRole($definition['role']);
        }

        $this->call([
            UserSeeder::class,
            TeamSeeder::class,
            TicketSeeder::class,
        ]);
    }
}
