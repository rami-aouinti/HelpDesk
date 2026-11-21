<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('HELPDESK_DEMO_PASSWORD', 'password');

        $agents = [
            ['name' => 'Sarah Miller', 'email' => 'sarah.agent@helpdesk.test'],
            ['name' => 'Daniel Weber', 'email' => 'daniel.agent@helpdesk.test'],
            ['name' => 'Amira Ben Ali', 'email' => 'amira.agent@helpdesk.test'],
            ['name' => 'Lucas Schneider', 'email' => 'lucas.agent@helpdesk.test'],
            ['name' => 'Emma Laurent', 'email' => 'emma.agent@helpdesk.test'],
            ['name' => 'Youssef Haddad', 'email' => 'youssef.agent@helpdesk.test'],
        ];

        $customers = [
            ['name' => 'Sophie Martin', 'email' => 'sophie.customer@helpdesk.test'],
            ['name' => 'Thomas Becker', 'email' => 'thomas.customer@helpdesk.test'],
            ['name' => 'Nour Trabelsi', 'email' => 'nour.customer@helpdesk.test'],
            ['name' => 'Maxime Dubois', 'email' => 'maxime.customer@helpdesk.test'],
            ['name' => 'Lina Fischer', 'email' => 'lina.customer@helpdesk.test'],
            ['name' => 'Adam Khelifi', 'email' => 'adam.customer@helpdesk.test'],
            ['name' => 'Clara Wagner', 'email' => 'clara.customer@helpdesk.test'],
            ['name' => 'Mehdi Gharbi', 'email' => 'mehdi.customer@helpdesk.test'],
        ];

        $this->seedUsers($agents, Role::findByName('agent'), $password);
        $this->seedUsers($customers, Role::findByName('customer'), $password);
    }

    /**
     * @param  array<int, array{name: string, email: string}>  $users
     */
    private function seedUsers(array $users, Role $role, string $password): void
    {
        foreach ($users as $attributes) {
            $user = User::firstOrCreate(
                ['email' => $attributes['email']],
                [
                    'name' => $attributes['name'],
                    'password' => $password,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            $user->assignRole($role);
        }
    }
}
