<?php

namespace App\Http\Controllers;

use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', Rule::in([
                'status', 'priority', 'assigned_to', 'team_id', 'project_id',
                'estimated_minutes', 'elapsed_minutes',
            ])],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $user = $request->user();
        $visibleTickets = function ($tickets) use ($user): void {
            if ($user->hasRole('customer')) {
                $tickets->where('user_id', $user->id);
            } elseif ($user->can('tickets.view.all')) {
                return;
            } elseif ($user->can('tickets.view') && $user->hasRole('agent')) {
                $teamIds = $user->teams()->pluck('teams.id');
                $tickets->where(function ($scope) use ($teamIds): void {
                    $scope->whereNull('team_id')->orWhereIn('team_id', $teamIds);
                })->where(function ($scope) use ($user): void {
                    $scope->whereNull('project_id')
                        ->orWhereHas('project.members', fn ($members) => $members->whereKey($user->id));
                });
            } else {
                $tickets->whereRaw('1 = 0');
            }
        };

        $baseQuery = TicketHistory::query()
            ->whereHas('ticket', $visibleTickets);

        $availableUserIds = (clone $baseQuery)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $availableUsers = User::query()
            ->whereIn('id', $availableUserIds)
            ->orderBy('name')
            ->get(['id', 'name', 'profile_photo_path']);

        $query = (clone $baseQuery)->with([
            'ticket:id,subject,project_id',
            'ticket.project:id,name',
            'user:id,name,profile_photo_path',
        ])->latest();

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user'])) {
            $query->where('user_id', $filters['user']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return view('activity.index', [
            'activities' => $query->paginate(20)->withQueryString(),
            'availableUsers' => $availableUsers,
            'filters' => $filters,
        ]);
    }
}
