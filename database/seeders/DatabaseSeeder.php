<?php

namespace Database\\Seeders;

use App\\Models\\User;
use Illuminate\\Database\\Seeder;
use Spatie\\Permission\\Models\\Role;
use Spatie\\Permission\\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->call(RolePermissionSeeder::class);

        $admin = Role::findByName('admin', 'web');
        $agent = Role::findByName('agent', 'web');
        $customer = Role::findByName('customer', 'web');
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
                    'is_active' => true,
                ],
            );

            $user->assignRole($definition['role']);
        }

        $this->call([
            UserSeeder::class,
            TeamSeeder::class,
            ProjectSeeder::class,
            TicketSeeder::class,
            SprintSeeder::class,
        ]);
    }
}
