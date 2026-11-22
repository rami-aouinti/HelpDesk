<?php

namespace App\Http\Controllers;

use App\Insights\Application\Queries\BuildReport\BuildReportHandler;
use App\Insights\Application\Queries\BuildReport\BuildReportQuery;
use App\Insights\Application\Queries\ExportReportCsv\ExportReportCsvHandler;
use App\Insights\Application\Queries\ExportReportCsv\ExportReportCsvQuery;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, BuildReportHandler $handler)
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
        $data = $handler->handle(new BuildReportQuery($filters));

        return view('reports.index', [
            'report' => $report,
            'filters' => $filters,
            ...$data,
        ]);
    }

    public function csv(Request $request, ExportReportCsvHandler $handler)
    {
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'team_id' => 'nullable|integer',
            'agent_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
        ]);

        $query = new ExportReportCsvQuery($filters);

        return response()->streamDownload(function () use ($handler, $query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'Subject', 'Status', 'Priority', 'Created', 'Customer', 'Agent', 'Team', 'Project', 'Tracked minutes']);

            foreach ($handler->handle($query) as $row) {
                fputcsv($out, $row->toCsv());
            }

            fclose($out);
        }, 'helpdesk-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
