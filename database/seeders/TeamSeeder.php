<?php

namespace Database\\Seeders;

use App\\Models\\Team;
use App\\Models\\User;
use Illuminate\\Database\\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            [
                'name' => 'General Support',
                'description' => 'First-line support for general customer questions.',
                'members' => ['agent@helpdesk.test', 'sarah.agent@helpdesk.test', 'daniel.agent@helpdesk.test', 'maya.agent@helpdesk.test'],
            ],
            [
                'name' => 'Technical Support',
                'description' => 'Handles technical incidents, bugs and troubleshooting.',
                'members' => ['amira.agent@helpdesk.test', 'lucas.agent@helpdesk.test', 'youssef.agent@helpdesk.test', 'jonas.agent@helpdesk.test', 'noah.agent@helpdesk.test'],
            ],
            [
                'name' => 'Billing Support',
                'description' => 'Handles invoices, payments and subscription questions.',
                'members' => ['sarah.agent@helpdesk.test', 'emma.agent@helpdesk.test', 'lea.agent@helpdesk.test'],
            ],
            [
                'name' => 'Account Management',
                'description' => 'Helps customers with accounts and access.',
                'members' => ['daniel.agent@helpdesk.test', 'amira.agent@helpdesk.test', 'youssef.agent@helpdesk.test', 'ines.agent@helpdesk.test'],
            ],
            [
                'name' => 'Product & Integrations',
                'description' => 'Owns product feedback, integrations and complex escalations.',
                'members' => ['karim.agent@helpdesk.test', 'maya.agent@helpdesk.test', 'jonas.agent@helpdesk.test', 'lea.agent@helpdesk.test'],
            ],
        ];

        foreach ($teams as $definition) {
            $team = Team::updateOrCreate(
                ['name' => $definition['name']],
                ['description' => $definition['description']],
            );

            $memberIds = User::query()
                ->whereIn('email', $definition['members'])
                ->where('is_active', true)
                ->role('agent')
                ->pluck('id')
                ->all();

            $team->members()->sync($memberIds);
        }
    }
}
