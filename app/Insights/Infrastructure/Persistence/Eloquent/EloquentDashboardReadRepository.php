<?php

namespace App\Insights\Infrastructure\Persistence\Eloquent;

use App\Insights\Application\Contracts\DashboardReadRepository;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketHistory;

final class EloquentDashboardReadRepository implements DashboardReadRepository
{
    /**
     * @param array{user_id: int, can_view_all: bool, is_customer: bool, is_agent: bool, is_admin: bool} $scope
     * @return array<string, mixed>
     */
    public function overview(array $scope): array
    {
        $tickets = Ticket::query();

        if (! $scope['can_view_all']) {
            if ($scope['is_customer']) {
                $tickets->where('user_id', $scope['user_id']);
            } elseif ($scope['is_agent']) {
                $teamIds = Team::query()
                    ->whereHas('members', fn ($members) => $members->whereKey($scope['user_id']))
                    ->pluck('teams.id');

                $tickets->where(function ($query) use ($teamIds): void {
                    $query->whereNull('team_id')
                        ->orWhereIn('team_id', $teamIds);
                });
            } else {
                $tickets->whereRaw('1 = 0');
            }

            // Preserve TicketPolicy visibility for project-linked tickets.
            $tickets->where(function ($query) use ($scope): void {
                $query->whereNull('project_id')
                    ->orWhereHas('project.members', function ($members) use ($scope): void {
                        $members->whereKey($scope['user_id']);
                    });
            });
        }

        $statistics = [
            'total' => (clone $tickets)->count(),
            'open' => (clone $tickets)->where('status', 'open')->count(),
            'in_progress' => (clone $tickets)->where('status', 'in_progress')->count(),
            'resolved' => (clone $tickets)->where('status', 'resolved')->count(),
        ];

        $priorityLabels = [
            'urgent' => 'Urgent',
            'high' => 'High',
            'normal' => 'Normal',
            'low' => 'Low',
        ];

        $priorityBreakdown = [];
        foreach ($priorityLabels as $priority => $label) {
            $count = (clone $tickets)->where('priority', $priority)->count();

            $priorityBreakdown[] = [
                'key' => $priority,
                'label' => $label,
                'count' => $count,
                'percentage' => $statistics['total'] > 0
                    ? (int) round(($count / $statistics['total']) * 100)
                    : 0,
            ];
        }

        $recentTickets = (clone $tickets)
            ->with(['customer', 'assignedAgent'])
            ->latest()
            ->limit(8)
            ->get();

        $recentActivity = TicketHistory::query()
            ->whereIn('ticket_id', (clone $tickets)->select('tickets.id'))
            ->with(['ticket', 'user'])
            ->latest()
            ->limit(6)
            ->get();

        $calendarTeams = $scope['is_admin']
            ? Team::query()->orderBy('name')->get(['id', 'name'])
            : Team::query()
                ->whereHas('members', fn ($members) => $members->whereKey($scope['user_id']))
                ->orderBy('name')
                ->get(['id', 'name']);

        return [
            'statistics' => $statistics,
            'priorityBreakdown' => $priorityBreakdown,
            'recentTickets' => $recentTickets,
            'recentActivity' => $recentActivity,
            'calendarTeams' => $calendarTeams,
        ];
    }
}
