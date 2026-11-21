<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Throwable;

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
    public function show(Ticket $ticket): View
    {
        $ticket->load(['customer', 'assignedAgent', 'histories.user']);

        return view('tickets.show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
        TicketService $ticketService
    ): RedirectResponse {
        $ticketService->update(
            $ticket,
            $request->validated(),
            auth()->id()
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully.');
    }
}
