<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $tickets = Ticket::query()
            ->when(auth()->user()->hasRole('customer'), fn ($query) => $query->where('user_id', auth()->id()));

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
