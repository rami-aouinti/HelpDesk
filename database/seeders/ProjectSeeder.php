<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $agents = User::role('agent')->where('is_active', true)->get();
        $customers = User::role('customer')->where('is_active', true)->get();

        $definitions = [
            ['name' => 'Website Redesign', 'description' => 'Customer portal redesign and accessibility improvements.', 'budget' => 15000, 'start_date' => now()->subDays(10)->toDateString(), 'end_date' => now()->addDays(50)->toDateString(), 'status' => 'active'],
            ['name' => 'Mobile Application', 'description' => 'Mobile app stability and authentication improvements.', 'budget' => 28000, 'start_date' => now()->addDays(10)->toDateString(), 'end_date' => now()->addDays(100)->toDateString(), 'status' => 'planned'],
            ['name' => 'Billing Modernization', 'description' => 'Invoice and payment reconciliation improvements.', 'budget' => 12000, 'start_date' => now()->subDays(30)->toDateString(), 'end_date' => now()->addDays(15)->toDateString(), 'status' => 'on_hold'],
        ];

        foreach ($definitions as $index => $definition) {
            $project = Project::updateOrCreate(
                ['name' => $definition['name']],
                [...$definition, 'created_by' => $admin->id],
            );

            $project->members()->sync(
                $agents->take(2)->merge($customers->take($index + 1))->modelKeys()
            );

            Ticket::query()
                ->whereNull('project_id')
                ->whereIn('user_id', $customers->take($index + 1)->modelKeys())
                ->limit(1)
                ->update(['project_id' => $project->id]);
        }
    }
}
