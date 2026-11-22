<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CalendarController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $from = $request->date('start')?->startOfDay() ?? now()->startOfMonth()->subMonth();
        $to = $request->date('end')?->endOfDay() ?? now()->endOfMonth()->addMonth();

        $events = CalendarEvent::query()
            ->with(['creator:id,name', 'ticket:id,subject'])
            ->where('starts_at', '<=', $to)
            ->where(function ($query) use ($from): void {
                $query->where('ends_at', '>', $from)
                    ->orWhere(function ($singleDay) use ($from): void {
                        $singleDay->whereNull('ends_at')->where('starts_at', '>=', $from);
                    });
            })
            ->where(function ($query) use ($user): void {
                $query->where('visibility', 'company')
                    ->orWhere(function ($personal) use ($user): void {
                        $personal->where('visibility', 'personal')->where('user_id', $user->id);
                    })
                    ->orWhere(function ($team) use ($user): void {
                        $team->where('visibility', 'team');
                        if (! $user->hasRole('admin')) {
                            $team->whereIn('team_id', $user->teams()->select('teams.id'));
                        }
                    });
            })
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $event): array => [
                'id' => 'event-' . $event->id,
                'event_id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start' => $event->starts_at->toIso8601String(),
                'end' => $event->ends_at?->toIso8601String(),
                'allDay' => $event->all_day,
                'type' => $event->type,
                'visibility' => $event->visibility,
                'team_id' => $event->team_id,
                'location' => $event->location,
                'creator' => $event->creator?->name,
                'ticket' => $event->ticket ? ['id' => $event->ticket->id, 'subject' => $event->ticket->subject] : null,
                'editable' => $event->isEditableBy($user),
                'source' => 'manual',
            ]);

        $ticketQuery = Ticket::query()->with('customer:id,name')
            ->whereBetween('created_at', [$from, $to]);
        $this->applyTicketVisibility($ticketQuery, $user);

        $ticketEvents = $ticketQuery->get()->map(fn (Ticket $ticket): array => [
            'id' => 'ticket-' . $ticket->id,
            'title' => 'Ticket created: ' . $ticket->subject,
            'start' => $ticket->created_at->toIso8601String(),
            'allDay' => false,
            'type' => 'ticket',
            'visibility' => 'personal',
            'editable' => false,
            'url' => route('tickets.show', $ticket),
            'source' => 'system',
        ]);

        return response()->json(['events' => $events->concat($ticketEvents)->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $data['user_id'] = $request->user()->id;
        $event = CalendarEvent::query()->create($data);

        return response()->json(['message' => 'Event created.', 'event' => ['id' => $event->id]], 201);
    }

    public function update(Request $request, CalendarEvent $event): JsonResponse
    {
        abort_unless($event->isEditableBy($request->user()), 403);
        $event->update($this->validatedData($request));

        return response()->json(['message' => 'Event updated.']);
    }

    public function destroy(Request $request, CalendarEvent $event): JsonResponse
    {
        abort_unless($event->isEditableBy($request->user()), 403);
        $event->delete();

        return response()->json(['message' => 'Event deleted.']);
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in(['meeting', 'reminder', 'maintenance', 'training', 'announcement', 'holiday', 'other'])],
            'visibility' => ['required', Rule::in(['personal', 'team', 'company'])],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'ticket_id' => ['nullable', 'integer', 'exists:tickets,id'],
            'location' => ['nullable', 'string', 'max:180'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'all_day' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $visibility = $data['visibility'];

        if ($visibility === 'company' && ! $user->hasRole('admin')) {
            throw ValidationException::withMessages(['visibility' => 'Only administrators can publish company-wide events.']);
        }

        if ($visibility === 'team') {
            if (! $data['team_id'] || (! $user->hasRole('admin') && ! $user->teams()->whereKey($data['team_id'])->exists())) {
                throw ValidationException::withMessages(['team_id' => 'Choose one of your teams.']);
            }
        } else {
            $data['team_id'] = null;
        }

        if (! empty($data['ticket_id'])) {
            $ticket = Ticket::query()->findOrFail($data['ticket_id']);
            $canViewTicket = $user->can('tickets.view.all')
                || ($user->hasRole('customer') && $ticket->user_id === $user->id)
                || ($user->hasRole('agent') && $user->can('tickets.view')
                    && ($ticket->team_id === null || $user->teams()->whereKey($ticket->team_id)->exists())
                    && ($ticket->project_id === null || $user->projects()->whereKey($ticket->project_id)->exists()));
            abort_unless($canViewTicket, 403);
        }

        $data['all_day'] = (bool) ($data['all_day'] ?? false);

        return $data;
    }

    private function applyTicketVisibility($query, User $user): void
    {
        if ($user->can('tickets.view.all')) {
            return;
        }

        if ($user->hasRole('customer')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('agent')) {
            $teamIds = $user->teams()->pluck('teams.id');
            $query->where(fn ($scope) => $scope->whereNull('team_id')->orWhereIn('team_id', $teamIds));
            $query->where(fn ($scope) => $scope->whereNull('project_id')
                ->orWhereHas('project.members', fn ($members) => $members->whereKey($user->id)));
        } else {
            $query->whereRaw('1 = 0');
        }
    }
}
