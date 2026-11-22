<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class WorkspaceDetailController extends Controller
{
    public function project(Project $project): View
    {
        Gate::authorize('view', $project);

        $user = request()->user();
        $ticketQuery = $project->tickets()->with(['customer', 'assignedAgent']);

        if ($user->hasRole('customer')) {
            $ticketQuery->where('user_id', $user->id);
        } elseif ($user->hasRole('agent') && ! $user->can('tickets.view.all')) {
            $teamIds = $user->teams()->pluck('teams.id');
            $ticketQuery->where(fn ($query) => $query->whereNull('team_id')->orWhereIn('team_id', $teamIds));
        }

        $project->load(['members', 'documents.uploader']);
        $project->setRelation('tickets', $ticketQuery->latest()->get());

        return view('projects.show', compact('project'));
    }

    public function team(Team $team): View
    {
        Gate::authorize('viewAny', Team::class);

        $team->load('members')->loadCount('tickets');
        $team->setRelation('tickets', $team->tickets()->with(['customer', 'assignedAgent'])->latest()->get());

        return view('teams.show', compact('team'));
    }

    public function sprint(Sprint $sprint): View
    {
        Gate::authorize('viewAny', Sprint::class);

        $sprint->load('creator')->loadCount('tickets')
            ->loadCount(['tickets as done_tickets_count' => fn ($query) => $query->where('status', 'closed')]);
        $sprint->setRelation('tickets', $sprint->tickets()->with(['customer', 'assignedAgent'])->latest()->get());

        return view('sprints.show', compact('sprint'));
    }
}
