<?php

namespace App\\Http\\Controllers;

use App\\Http\\Requests\\StoreTicketRequest;
use App\\Http\\Requests\\UpdateTicketRequest;
use App\\Models\\Ticket;
use App\\Services\\TicketService;
use Illuminate\\Contracts\\View\\View;
use Illuminate\\Http\\RedirectResponse;
use Throwable;

class TicketController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->can('viewAny', Ticket::class), 403);

        return view('tickets.index');
    }

    public function create(): View
    {
        abort_unless(auth()->user()->can('create', Ticket::class), 403);

        return view('tickets.create');
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = Ticket::create([
            ...$request->validated(),
            'status' => 'open',
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('tickets.index')
            ->with('success', "Ticket #{$ticket->id} created successfully.");
    }

    public function show(Ticket $ticket): View
    {
        abort_unless(auth()->user()->can('view', $ticket), 403);

        $ticket->load(['customer', 'assignedAgent', 'histories.user']);

        return view('tickets.show', ['ticket' => $ticket]);
    }

    /**
     * @throws Throwable
     */
    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
        TicketService $ticketService
    ): RedirectResponse {
        $ticketService->update($ticket, $request->validated(), $request->user()->id);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully.');
    }
}
