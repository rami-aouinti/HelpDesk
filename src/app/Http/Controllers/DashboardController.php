<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Contracts\View\View;

/**
 * @package App\Http\Controllers
 * @author Rami Aouinti
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $statistics = [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
        ];

        $recentTickets = Ticket::query()
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
