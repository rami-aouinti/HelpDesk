<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $tickets = Ticket::query()
            ->when(! $user->can('tickets.view.all'), fn ($query) => $query->where('user_id', $user->id));

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
