<?php

namespace App\\Http\\Controllers;

use App\\Models\\Ticket;
use Illuminate\\Contracts\\View\\View;

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

        $recentTickets = (clone $tickets)
            ->with(['customer', 'assignedAgent'])
            ->latest()
            ->limit(8)
            ->get();

        return view('dashboard.index', [
            'statistics' => $statistics,
            'recentTickets' => $recentTickets,
        ]);
    }
}
