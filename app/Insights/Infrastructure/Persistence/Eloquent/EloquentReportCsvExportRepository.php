<?php

namespace App\Insights\Infrastructure\Persistence\Eloquent;

use App\Insights\Application\Contracts\ReportCsvExportRepository;
use App\Insights\Application\DTO\ReportCsvRow;
use Illuminate\Support\Facades\DB;

final class EloquentReportCsvExportRepository implements ReportCsvExportRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return iterable<ReportCsvRow>
     */
    public function rows(array $filters): iterable
    {
        $query = DB::table('tickets')
            ->leftJoin('users as customer', 'tickets.user_id', '=', 'customer.id')
            ->leftJoin('users as agent', 'tickets.assigned_to', '=', 'agent.id')
            ->leftJoin('teams', 'tickets.team_id', '=', 'teams.id')
            ->leftJoin('projects', 'tickets.project_id', '=', 'projects.id')
            ->select([
                'tickets.id',
                'tickets.subject',
                'tickets.status',
                'tickets.priority',
                'tickets.created_at',
                'customer.name as customer',
                'agent.name as agent',
                'teams.name as team',
                'projects.name as project',
                'tickets.elapsed_minutes',
            ])
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('tickets.created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('tickets.created_at', '<=', $date))
            ->when($filters['team_id'] ?? null, fn ($q, $id) => $q->where('tickets.team_id', $id))
            ->when($filters['agent_id'] ?? null, fn ($q, $id) => $q->where('tickets.assigned_to', $id))
            ->when($filters['project_id'] ?? null, fn ($q, $id) => $q->where('tickets.project_id', $id))
            ->orderBy('tickets.id');

        foreach ($query->cursor() as $row) {
            yield new ReportCsvRow(
                id: (int) $row->id,
                subject: (string) $row->subject,
                status: (string) $row->status,
                priority: (string) $row->priority,
                createdAt: $row->created_at,
                customer: $row->customer,
                agent: $row->agent,
                team: $row->team,
                project: $row->project,
                trackedMinutes: (int) ($row->elapsed_minutes ?? 0),
            );
        }
    }
}
