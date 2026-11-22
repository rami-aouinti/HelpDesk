<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketHistory;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $tickets = Ticket::query();

        if (! $user->can('tickets.view.all')) {
            if ($user->hasRole('customer')) {
                $tickets->where('user_id', $user->id);
            } elseif ($user->hasRole('agent')) {
                $teamIds = $user->teams()->pluck('teams.id');

                $tickets->where(function ($scope) use ($teamIds): void {
                    $scope->whereNull('team_id')
                        ->orWhereIn('team_id', $teamIds);
                });
            } else {
                $tickets->whereRaw('1 = 0');
            }

            // Keep dashboard visibility aligned with TicketPolicy: a ticket
            // attached to a project is visible only to that project's members.
            $tickets->where(function ($scope) use ($user): void {
                $scope->whereNull('project_id')
                    ->orWhereHas('project.members', function ($members) use ($user): void {
                        $members->whereKey($user->id);
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

        $dashboardAudience = match (true) {
            $user->can('tickets.view.all') => 'Workspace overview',
            $user->hasRole('agent') => 'Your team queue',
            $user->hasRole('customer') => 'Your support requests',
            default => 'Your workspace',
        };

        return view('dashboard.index', [
            'statistics' => $statistics,
            'priorityBreakdown' => $priorityBreakdown,
            'recentTickets' => $recentTickets,
            'recentActivity' => $recentActivity,
            'dashboardAudience' => $dashboardAudience,\n            'calendarTeams' => $user->teams()->orderBy('name')->get(['teams.id', 'teams.name']),
        ]);
    }
}
