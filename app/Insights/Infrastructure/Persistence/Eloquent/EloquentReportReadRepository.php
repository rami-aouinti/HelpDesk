<?php

namespace App\Insights\Infrastructure\Persistence\Eloquent;

use App\Insights\Application\Contracts\ReportReadRepository;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class EloquentReportReadRepository implements ReportReadRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function build(array $filters): array
    {
        $query = DB::table('tickets')
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('tickets.created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('tickets.created_at', '<=', $date))
            ->when($filters['team_id'] ?? null, fn ($q, $id) => $q->where('tickets.team_id', $id))
            ->when($filters['agent_id'] ?? null, fn ($q, $id) => $q->where('tickets.assigned_to', $id))
            ->when($filters['project_id'] ?? null, fn ($q, $id) => $q->where('tickets.project_id', $id));

        $total = (clone $query)->count();
        $resolved = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();

        return [
            'total' => $total,
            'resolved' => $resolved,
            'open' => (clone $query)->whereNotIn('status', ['resolved', 'closed'])->count(),
            'urgent' => (clone $query)->where('priority', 'urgent')->count(),
            'resolutionRate' => $total > 0 ? round($resolved / $total * 100, 1) : 0,
            'statuses' => (clone $query)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->orderByDesc('total')
                ->get(),
            'priorities' => (clone $query)
                ->select('priority', DB::raw('COUNT(*) as total'))
                ->groupBy('priority')
                ->orderByDesc('total')
                ->get(),
            'ticketRows' => (clone $query)
                ->leftJoin('users as customers', 'tickets.user_id', '=', 'customers.id')
                ->leftJoin('users as assigned_agents', 'tickets.assigned_to', '=', 'assigned_agents.id')
                ->select([
                    'tickets.id',
                    'tickets.subject',
                    'tickets.status',
                    'tickets.priority',
                    'tickets.created_at',
                    'customers.name as customer_name',
                    'assigned_agents.name as agent_name',
                ])
                ->orderByDesc('tickets.created_at')
                ->limit(100)
                ->get(),
            'agents' => (clone $query)
                ->leftJoin('users', 'tickets.assigned_to', '=', 'users.id')
                ->selectRaw("COALESCE(users.name, 'Unassigned') as name, COUNT(*) as total, SUM(CASE WHEN tickets.status IN ('resolved','closed') THEN 1 ELSE 0 END) as resolved, SUM(COALESCE(tickets.elapsed_minutes,0)) as minutes")
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total')
                ->get(),
            'teamsReport' => (clone $query)
                ->leftJoin('teams', 'tickets.team_id', '=', 'teams.id')
                ->selectRaw("COALESCE(teams.name, 'No team') as name, COUNT(*) as total, SUM(CASE WHEN tickets.status IN ('resolved','closed') THEN 1 ELSE 0 END) as resolved")
                ->groupBy('teams.id', 'teams.name')
                ->orderByDesc('total')
                ->get(),
            'projectsReport' => Project::query()
                ->withCount('tickets')
                ->withSum('tickets', 'elapsed_minutes')
                ->when($filters['project_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
                ->orderBy('status')
                ->get(),
            'teams' => Team::query()->orderBy('name')->get(['id', 'name']),
            'agentOptions' => User::role('agent')->orderBy('name')->get(['id', 'name']),
            'projectOptions' => Project::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
