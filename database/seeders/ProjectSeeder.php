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
            ['name' => 'Mobile Application', 'description' => 'Mobile app stability and authentication improvements.', 'budget' => 28000, 'start_date' => now()->subDays(5)->toDateString(), 'end_date' => now()->addDays(85)->toDateString(), 'status' => 'active'],
            ['name' => 'Billing Modernization', 'description' => 'Invoice and payment reconciliation improvements.', 'budget' => 12000, 'start_date' => now()->subDays(30)->toDateString(), 'end_date' => now()->addDays(15)->toDateString(), 'status' => 'on_hold'],
            ['name' => 'Customer Portal V2', 'description' => 'Self-service workflows, account settings and ticket visibility.', 'budget' => 22000, 'start_date' => now()->subDays(20)->toDateString(), 'end_date' => now()->addDays(70)->toDateString(), 'status' => 'active'],
            ['name' => 'Knowledge Base', 'description' => 'Searchable help center and guided troubleshooting articles.', 'budget' => 9000, 'start_date' => now()->addDays(5)->toDateString(), 'end_date' => now()->addDays(65)->toDateString(), 'status' => 'planned'],
            ['name' => 'Identity & Security', 'description' => 'MFA, session management and account security hardening.', 'budget' => 18000, 'start_date' => now()->subDays(45)->toDateString(), 'end_date' => now()->addDays(20)->toDateString(), 'status' => 'active'],
            ['name' => 'Reporting & Analytics', 'description' => 'Operational dashboards, exports and service-level reporting.', 'budget' => 11000, 'start_date' => now()->addDays(12)->toDateString(), 'end_date' => now()->addDays(75)->toDateString(), 'status' => 'planned'],
            ['name' => 'API Integrations', 'description' => 'Reliable integrations, webhooks and external service connections.', 'budget' => 19500, 'start_date' => now()->subDays(60)->toDateString(), 'end_date' => now()->subDays(5)->toDateString(), 'status' => 'completed'],
        ];

        foreach ($definitions as $index => $definition) {
            $project = Project::updateOrCreate(
                ['name' => $definition['name']],
                [...$definition, 'created_by' => $admin->id],
            );

            $project->members()->sync(
                $agents->slice($index % 4, 4)->merge($customers->slice($index, 3))->modelKeys()
            );

            Ticket::query()
                ->whereNull('project_id')
                ->whereIn('user_id', $customers->slice($index, 3)->modelKeys())
                ->limit(2)
                ->update(['project_id' => $project->id]);
        }
    }
}
