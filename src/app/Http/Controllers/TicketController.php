<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Team;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $ticket->load(['customer', 'assignedAgent', 'team', 'histories.user']);

        $messagesQuery = $ticket->messages()->with('user')->oldest();
        if (auth()->user()->hasRole('customer')) {
            $messagesQuery->where('is_internal', false);
        }

        return view('tickets.show', [
            'ticket' => $ticket,
            'messages' => $messagesQuery->get(),
            'teams' => Team::query()->with('members')->orderBy('name')->get(),
            'agents' => User::query()->role('agent')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeMessage(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($request->user()->can('view', $ticket), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:10000'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $isInternal = $request->boolean('is_internal');
        if ($isInternal) {
            abort_unless($request->user()->can('update', $ticket), 403);
        }

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'is_internal' => $isInternal,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', $isInternal ? 'Internal note added.' : 'Reply sent successfully.');
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
