<?php

namespace Database\Seeders;

use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class SprintSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $admin = User::role('admin')->firstOrFail();

        $definitions = [
            ['name' => 'Sprint 01 - Foundation', 'description' => 'Authentication, user management and initial support workflows.', 'start' => -56],
            ['name' => 'Sprint 02 - Ticket Workflow', 'description' => 'Ticket lifecycle, assignment, priorities and team collaboration.', 'start' => -42],
            ['name' => 'Sprint 03 - Project Management', 'description' => 'Projects, documents, time tracking and reporting foundations.', 'start' => -28],
            ['name' => 'Sprint 04 - HelpDesk Platform V2', 'description' => 'Kanban improvements, notifications, search and customer experience.', 'start' => -14],
            ['name' => 'Sprint 05 - Current Iteration', 'description' => 'Current sprint: stability, integrations and operational improvements.', 'start' => -6],
        ];

        $sprints = [];

        foreach ($definitions as $definition) {
            $startDate = now()->addDays($definition['start'])->toDateString();

            $sprints[] = Sprint::updateOrCreate(
                ['name' => $definition['name']],
                [
                    'description' => $definition['description'],
                    'start_date' => $startDate,
                    'end_date' => now()->addDays($definition['start'] + 13)->toDateString(),
                    'created_by' => $admin->id,
                ],
            );
        }

        $tickets = Ticket::query()->orderBy('id')->get();

        foreach ($tickets as $index => $ticket) {
            $sprint = $sprints[$index % count($sprints)];
            $ticket->update(['sprint_id' => $sprint->id]);
        }
    }
}
