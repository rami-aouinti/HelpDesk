<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TicketSatisfactionSurvey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CsatReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        $query = TicketSatisfactionSurvey::query()
            ->with(['ticket:id,subject', 'customer:id,name', 'agent:id,name', 'team:id,name'])
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('submitted_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('submitted_at', '<=', $date))
            ->when($filters['agent_id'] ?? null, fn ($q, $id) => $q->where('agent_id', $id))
            ->when($filters['team_id'] ?? null, fn ($q, $id) => $q->where('team_id', $id));

        $surveys = (clone $query)->latest('submitted_at')->paginate(15)->withQueryString();
        $summary = (clone $query)->selectRaw('COUNT(*) as responses, AVG(rating) as average_rating, COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_responses')->first();
        $byAgent = (clone $query)->selectRaw('agent_id, COUNT(*) as responses, AVG(rating) as average_rating')->with('agent:id,name')->groupBy('agent_id')->orderByDesc('average_rating')->get();
        $byTeam = (clone $query)->selectRaw('team_id, COUNT(*) as responses, AVG(rating) as average_rating')->with('team:id,name')->groupBy('team_id')->orderByDesc('average_rating')->get();

        return view('reports.csat', [
            'filters' => $filters,
            'surveys' => $surveys,
            'summary' => $summary,
            'byAgent' => $byAgent,
            'byTeam' => $byTeam,
            'agents' => User::query()->role('agent')->orderBy('name')->get(['id', 'name']),
            'teams' => Team::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
