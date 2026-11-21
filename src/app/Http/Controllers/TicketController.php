<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * @package App\Http\Controllers
 * @author Rami Aouinti
 */
class TicketController extends Controller
{
    public function index(): View
    {
        $tickets = Ticket::query()
            ->with(['customer', 'assignedAgent'])
            ->latest()
            ->paginate(10);

        return view('tickets.index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(): View
    {
        return view('tickets.create');
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = Ticket::create([
            ...$request->validated(),
            'status' => 'open',
        ]);

        return redirect()
            ->route('tickets.index')
            ->with('success', "Ticket #{$ticket->id} created successfully.");
    }
}
