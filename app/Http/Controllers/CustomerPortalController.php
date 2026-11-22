<?php

namespace App\\Http\\Controllers;

use App\\Models\\KnowledgeBaseArticle;
use App\\Models\\Ticket;
use App\\Models\\TicketSatisfactionSurvey;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\View\\View;

class CustomerPortalController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole('customer'), 403);

        $tickets = Ticket::query()
            ->where('user_id', $request->user()->id)
            ->with(['assignedAgent', 'team'])
            ->latest()
            ->paginate(10);

        $stats = [
            'total' => Ticket::query()->where('user_id', $request->user()->id)->count(),
            'open' => Ticket::query()->where('user_id', $request->user()->id)->whereNotIn('status', ['resolved', 'closed'])->count(),
            'resolved' => Ticket::query()->where('user_id', $request->user()->id)->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        $articles = KnowledgeBaseArticle::query()->published()->latest('published_at')->limit(4)->get();

        return view('customer-portal.index', compact('tickets', 'stats', 'articles'));
    }

    public function satisfaction(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($request->user()->hasRole('customer') && $ticket->user_id === $request->user()->id, 404);
        abort_unless(in_array($ticket->status, ['resolved', 'closed'], true), 403);
        abort_if(TicketSatisfactionSurvey::query()->where('ticket_id', $ticket->id)->exists(), 409, 'You have already rated this ticket.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        TicketSatisfactionSurvey::query()->create([
            'ticket_id' => $ticket->id,
            'customer_id' => $request->user()->id,
            'agent_id' => $ticket->assigned_to,
            'team_id' => $ticket->team_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'submitted_at' => now(),
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Thank you for your feedback!');
    }
}
