<?php

namespace App\Http\Controllers;

use App\Insights\Application\Queries\BuildReport\BuildReportHandler;
use App\Insights\Application\Queries\BuildReport\BuildReportQuery;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, BuildReportHandler $handler)
    {
        $filters = $request->validate([
            'report' => 'nullable|in:executive,tickets,agents,teams,projects',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'team_id' => 'nullable|integer|exists:teams,id',
            'agent_id' => 'nullable|integer|exists:users,id',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);

        $report = $filters['report'] ?? 'executive';
        $data = $handler->handle(new BuildReportQuery($filters));

        return view('reports.index', [
            'report' => $report,
            'filters' => $filters,
            ...$data,
        ]);
    }

    public function csv(Request $request)
    {
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'team_id' => 'nullable|integer',
            'agent_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
        ]);

        $query = \Illuminate\Support\Facades\DB::table('tickets')
            ->leftJoin('users as customer', 'tickets.user_id', '=', 'customer.id')
            ->leftJoin('users as agent', 'tickets.assigned_to', '=', 'agent.id')
            ->leftJoin('teams', 'tickets.team_id', '=', 'teams.id')
            ->leftJoin('projects', 'tickets.project_id', '=', 'projects.id')
            ->select(
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
            )
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('tickets.created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('tickets.created_at', '<=', $date))
            ->when($filters['team_id'] ?? null, fn ($q, $id) => $q->where('tickets.team_id', $id))
            ->when($filters['agent_id'] ?? null, fn ($q, $id) => $q->where('tickets.assigned_to', $id))
            ->when($filters['project_id'] ?? null, fn ($q, $id) => $q->where('tickets.project_id', $id));

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'Subject', 'Status', 'Priority', 'Created', 'Customer', 'Agent', 'Team', 'Project', 'Tracked minutes']);

            $query->orderByDesc('tickets.created_at')->chunk(500, function ($rows) use ($out): void {
                foreach ($rows as $row) {
                    fputcsv($out, (array) $row);
                }
            });

            fclose($out);
        }, 'helpdesk-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
