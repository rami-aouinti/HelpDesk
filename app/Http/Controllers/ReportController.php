<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'report' => 'nullable|in:executive,tickets,agents,teams,projects',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'team_id' => 'nullable|integer|exists:teams,id',
            'agent_id' => 'nullable|integer|exists:users,id',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);
        $report = $filters['report'] ?? 'executive';
        $query = DB::table('tickets')
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('tickets.created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('tickets.created_at', '<=', $date))
            ->when($filters['team_id'] ?? null, fn ($q, $id) => $q->where('tickets.team_id', $id))
            ->when($filters['agent_id'] ?? null, fn ($q, $id) => $q->where('tickets.assigned_to', $id))
            ->when($filters['project_id'] ?? null, fn ($q, $id) => $q->where('tickets.project_id', $id));
        $total = (clone $query)->count();
        $resolved = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();
        $open = (clone $query)->whereNotIn('status', ['resolved', 'closed'])->count();
        $urgent = (clone $query)->where('priority', 'urgent')->count();
        $statuses = (clone $query)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->orderByDesc('total')->get();
        $priorities = (clone $query)->select('priority', DB::raw('COUNT(*) as total'))->groupBy('priority')->orderByDesc('total')->get();
        $agents = (clone $query)->leftJoin('users', 'tickets.assigned_to', '=', 'users.id')
            ->selectRaw("COALESCE(users.name, 'Unassigned') as name, COUNT(*) as total, SUM(CASE WHEN tickets.status IN ('resolved','closed') THEN 1 ELSE 0 END) as resolved, SUM(COALESCE(tickets.elapsed_minutes,0)) as minutes")
            ->groupBy('users.id','users.name')->orderByDesc('total')->get();
        $teamsReport = (clone $query)->leftJoin('teams','tickets.team_id','=','teams.id')
            ->selectRaw("COALESCE(teams.name,'No team') as name, COUNT(*) as total, SUM(CASE WHEN tickets.status IN ('resolved','closed') THEN 1 ELSE 0 END) as resolved")
            ->groupBy('teams.id','teams.name')->orderByDesc('total')->get();
        $projectsReport = Project::query()->withCount('tickets')->withSum('tickets','elapsed_minutes')
            ->when($filters['project_id'] ?? null, fn ($q,$id) => $q->whereKey($id))->orderBy('status')->get();
        return view('reports.index', [
            'report'=>$report,'filters'=>$filters,'total'=>$total,'resolved'=>$resolved,'open'=>$open,'urgent'=>$urgent,
            'resolutionRate'=>$total ? round($resolved/$total*100,1) : 0,'statuses'=>$statuses,'priorities'=>$priorities,
            'agents'=>$agents,'teamsReport'=>$teamsReport,'projectsReport'=>$projectsReport,
            'teams'=>Team::orderBy('name')->get(['id','name']),
            'agentOptions'=>User::role('agent')->orderBy('name')->get(['id','name']),
            'projectOptions'=>Project::orderBy('name')->get(['id','name']),
        ]);
    }

    public function csv(Request $request)
    {
        $filters = $request->validate(['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from','team_id'=>'nullable|integer','agent_id'=>'nullable|integer','project_id'=>'nullable|integer']);
        $query = DB::table('tickets')->leftJoin('users as customer','tickets.user_id','=','customer.id')
            ->leftJoin('users as agent','tickets.assigned_to','=','agent.id')->leftJoin('teams','tickets.team_id','=','teams.id')
            ->leftJoin('projects','tickets.project_id','=','projects.id')
            ->select('tickets.id','tickets.subject','tickets.status','tickets.priority','tickets.created_at','customer.name as customer','agent.name as agent','teams.name as team','projects.name as project','tickets.elapsed_minutes')
            ->when($filters['from']??null,fn($q,$d)=>$q->whereDate('tickets.created_at','>=',$d))
            ->when($filters['to']??null,fn($q,$d)=>$q->whereDate('tickets.created_at','<=',$d))
            ->when($filters['team_id']??null,fn($q,$id)=>$q->where('tickets.team_id',$id))
            ->when($filters['agent_id']??null,fn($q,$id)=>$q->where('tickets.assigned_to',$id))
            ->when($filters['project_id']??null,fn($q,$id)=>$q->where('tickets.project_id',$id));
        return response()->streamDownload(function () use ($query) {
            $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF");
            fputcsv($out,['ID','Subject','Status','Priority','Created','Customer','Agent','Team','Project','Tracked minutes']);
            $query->orderByDesc('tickets.created_at')->chunk(500,function($rows)use($out){foreach($rows as $row)fputcsv($out,(array)$row);});
            fclose($out);
        },'helpdesk-report.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}