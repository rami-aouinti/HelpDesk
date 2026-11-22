@extends('layouts.app')
@section('title','Reports & Analytics | HelpDesk')
@section('page-title','Reports & Analytics')
@section('content')
@php
    $statusChart = $statuses->map(fn ($row) => ['label' => ucfirst(str_replace('_', ' ', $row->status)), 'value' => (int) $row->total])->values();
    $priorityChart = $priorities->map(fn ($row) => ['label' => ucfirst($row->priority), 'value' => (int) $row->total])->values();
    $agentChart = $agents->take(8)->map(fn ($row) => ['label' => $row->name, 'value' => (int) $row->total])->values();
    $teamChart = $teamsReport->take(8)->map(fn ($row) => ['label' => $row->name, 'value' => (int) $row->total])->values();
    $projectChart = $projectsReport->take(8)->map(fn ($row) => ['label' => $row->name, 'value' => (int) $row->tickets_count])->values();
@endphp
<div class="reports-page">
    <header class="reports-heading">
        <div><h1>Reports & Analytics</h1><p>Executive insights and operational performance.</p></div>
        <div class="reports-actions">
            <a class="btn btn-secondary" href="{{ route('reports.csv', request()->query()) }}">Export CSV</a>
            <button class="btn btn-primary" type="button" id="report-pdf">Download PDF</button>
        </div>
    </header>

    <nav class="reports-tabs">
        @foreach(['executive'=>'Executive Overview','tickets'=>'Ticket Performance','agents'=>'Agent Performance','teams'=>'Team Workload','projects'=>'Project Health'] as $key=>$label)
            <a class="{{ $report===$key?'active':'' }}" href="{{ route('reports.index',array_merge(request()->query(),['report'=>$key])) }}">{{ $label }}</a>
        @endforeach
    </nav>

    <form class="content-card reports-filters" method="GET">
        <input type="hidden" name="report" value="{{ $report }}">
        <label>From<input type="date" name="from" value="{{ $filters['from']??'' }}"></label>
        <label>To<input type="date" name="to" value="{{ $filters['to']??'' }}"></label>
        <label>Team<select name="team_id"><option value="">All teams</option>@foreach($teams as $item)<option value="{{ $item->id }}" @selected(($filters['team_id']??'')==$item->id)>{{ $item->name }}</option>@endforeach</select></label>
        <label>Agent<select name="agent_id"><option value="">All agents</option>@foreach($agentOptions as $item)<option value="{{ $item->id }}" @selected(($filters['agent_id']??'')==$item->id)>{{ $item->name }}</option>@endforeach</select></label>
        <label>Project<select name="project_id"><option value="">All projects</option>@foreach($projectOptions as $item)<option value="{{ $item->id }}" @selected(($filters['project_id']??'')==$item->id)>{{ $item->name }}</option>@endforeach</select></label>
        <button class="btn btn-primary">Apply</button><a class="btn btn-secondary" href="{{ route('reports.index',['report'=>$report]) }}">Reset</a>
    </form>

    <div id="report-area" data-report="{{ $report }}">
        <div class="report-document-heading">
            <div><span class="report-eyebrow">HELPDESK ANALYTICS</span><h2>{{ ucfirst($report) }} report</h2><p>Generated {{ now()->format('d.m.Y H:i') }}</p></div>
            <span class="report-period">{{ ($filters['from']??null) ? \Carbon\Carbon::parse($filters['from'])->format('d.m.Y') : 'All time' }} – {{ ($filters['to']??null) ? \Carbon\Carbon::parse($filters['to'])->format('d.m.Y') : 'Today' }}</span>
        </div>

        <section class="reports-kpis">
            <article class="content-card"><span>Total tickets</span><strong>{{ number_format($total) }}</strong><small>In selected scope</small></article>
            <article class="content-card"><span>Open workload</span><strong>{{ number_format($open) }}</strong><small>Still in progress</small></article>
            <article class="content-card"><span>Resolved / closed</span><strong>{{ number_format($resolved) }}</strong><small>{{ $resolutionRate }}% resolution share</small></article>
            <article class="content-card"><span>Urgent tickets</span><strong>{{ number_format($urgent) }}</strong><small>Priority requiring attention</small></article>
        </section>

        @if(in_array($report,['executive','tickets']))
            <div class="reports-chart-grid">
                <section class="content-card report-chart"><h3>Tickets by status</h3>
                    @forelse($statusChart as $item)
                        <div class="chart-row"><span>{{ $item['label'] }}</span><div class="chart-track"><i style="width:{{ $total ? ($item['value']/$total*100) : 0 }}%"></i></div><b>{{ $item['value'] }}</b></div>
                    @empty<p class="report-empty">No status data for this selection.</p>@endforelse
                </section>
                <section class="content-card report-chart"><h3>Tickets by priority</h3>
                    @forelse($priorityChart as $item)
                        <div class="chart-row"><span>{{ $item['label'] }}</span><div class="chart-track"><i style="width:{{ $total ? ($item['value']/$total*100) : 0 }}%"></i></div><b>{{ $item['value'] }}</b></div>
                    @empty<p class="report-empty">No priority data for this selection.</p>@endforelse
                </section>
            </div>
        @endif

        @if(in_array($report,['executive','agents']))
            <section class="content-card report-section"><h3>Agent performance</h3>
                <div class="table-responsive"><table class="admin-data-table"><thead><tr><th>Agent</th><th>Assigned</th><th>Resolved</th><th>Tracked hours</th><th>Resolution share</th></tr></thead><tbody>
                @forelse($agents as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->total }}</td><td>{{ $row->resolved }}</td><td>{{ number_format($row->minutes/60,1) }}</td><td>{{ $row->total?round($row->resolved/$row->total*100,1):0 }}%</td></tr>@empty<tr><td colspan="5">No activity</td></tr>@endforelse
                </tbody></table></div>
            </section>
        @endif

        @if(in_array($report,['executive','teams']))
            <section class="content-card report-section"><h3>Team workload</h3>
                <div class="table-responsive"><table class="admin-data-table"><thead><tr><th>Team</th><th>Tickets</th><th>Resolved</th><th>Open</th></tr></thead><tbody>
                @forelse($teamsReport as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->total }}</td><td>{{ $row->resolved }}</td><td>{{ $row->total-$row->resolved }}</td></tr>@empty<tr><td colspan="4">No activity</td></tr>@endforelse
                </tbody></table></div>
            </section>
        @endif

        @if(in_array($report,['executive','projects']))
            <section class="content-card report-section"><h3>Project health</h3>
                <div class="table-responsive"><table class="admin-data-table"><thead><tr><th>Project</th><th>Status</th><th>Tickets</th><th>Tracked hours</th><th>Budget</th><th>End date</th></tr></thead><tbody>
                @forelse($projectsReport as $row)<tr><td>{{ $row->name }}</td><td>{{ ucfirst(str_replace('_',' ',$row->status)) }}</td><td>{{ $row->tickets_count }}</td><td>{{ number_format(($row->tickets_sum_elapsed_minutes??0)/60,1) }}</td><td>{{ $row->budget!==null?number_format((float)$row->budget,2).' €':'—' }}</td><td>{{ $row->end_date?->format('d.m.Y')??'—' }}</td></tr>@empty<tr><td colspan="6">No projects</td></tr>@endforelse
                </tbody></table></div>
            </section>
        @endif

        @if(in_array($report,['executive','tickets']))
            <section class="content-card report-section">
                <h3>Ticket details</h3>
                <div class="table-responsive">
                    <table class="admin-data-table">
                        <thead><tr><th>Ticket</th><th>Customer</th><th>Agent</th><th>Status</th><th>Priority</th><th>Created</th></tr></thead>
                        <tbody>
                        @forelse($ticketRows as $ticket)
                            <tr>
                                <td>{{ $ticket->subject }}</td>
                                <td>{{ $ticket->customer_name ?? '—' }}</td>
                                <td>{{ $ticket->agent_name ?? 'Unassigned' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</td>
                                <td>{{ ucfirst($ticket->priority) }}</td>
                                <td>{{ $ticket->created_at ? \Carbon\Carbon::parse($ticket->created_at)->format('d.m.Y') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No tickets for this selection.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($ticketRows->count() === 100)
                    <p class="report-note">Showing the latest 100 tickets in this selection. Export CSV for the complete result.</p>
                @endif
            </section>
            <div class="reports-chart-grid reports-chart-grid-secondary">
                <section class="content-card report-section"><h3>Status breakdown</h3><div class="table-responsive"><table class="admin-data-table"><thead><tr><th>Status</th><th>Tickets</th><th>Share</th></tr></thead><tbody>
                    @forelse($statuses as $row)<tr><td>{{ ucfirst(str_replace('_',' ',$row->status)) }}</td><td>{{ $row->total }}</td><td>{{ $total?round($row->total/$total*100,1):0 }}%</td></tr>@empty<tr><td colspan="3">No data</td></tr>@endforelse
                </tbody></table></div></section>
                <section class="content-card report-section"><h3>Priority breakdown</h3><div class="table-responsive"><table class="admin-data-table"><thead><tr><th>Priority</th><th>Tickets</th><th>Share</th></tr></thead><tbody>
                    @forelse($priorities as $row)<tr><td>{{ ucfirst($row->priority) }}</td><td>{{ $row->total }}</td><td>{{ $total?round($row->total/$total*100,1):0 }}%</td></tr>@empty<tr><td colspan="3">No data</td></tr>@endforelse
                </tbody></table></div></section>
            </div>
        @endif

        <p class="report-note">SLA compliance and customer satisfaction are not shown because HelpDesk does not yet collect SLA targets or feedback scores. Tracked hours are not payroll costs.</p>
    </div>
</div>

<style>
.reports-page{display:grid;gap:16px}.reports-heading{display:flex;justify-content:space-between;align-items:center;gap:12px}.reports-heading h1{margin:0}.reports-heading p,.report-note,.report-empty{color:var(--hd-muted)}.reports-actions{display:flex;gap:8px}.reports-tabs{display:flex;gap:8px;flex-wrap:wrap;border-bottom:1px solid var(--hd-border);padding-bottom:10px}.reports-tabs a{padding:9px 12px;border-radius:8px;text-decoration:none;color:var(--hd-muted)}.reports-tabs a.active{background:var(--hd-surface);color:var(--hd-ink);font-weight:700}.reports-filters{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.reports-filters label{display:grid;gap:5px;flex:1;min-width:130px}.reports-filters input,.reports-filters select{min-height:40px;padding:7px;border:1px solid var(--hd-border);border-radius:8px;background:var(--hd-surface);color:var(--hd-ink)}.report-document-heading{display:flex;align-items:end;justify-content:space-between;gap:16px;margin:8px 0 14px}.report-document-heading h2{margin:3px 0}.report-document-heading p{margin:0;color:var(--hd-muted)}.report-eyebrow{font-size:10px;letter-spacing:1.5px;font-weight:800;color:var(--hd-muted)}.report-period{font-size:12px;color:var(--hd-muted)}.reports-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.reports-kpis article{display:grid;gap:7px}.reports-kpis span,.reports-kpis small{color:var(--hd-muted)}.reports-kpis strong{font-size:27px;color:var(--hd-ink)}.reports-chart-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:14px}.report-chart{padding:18px}.report-chart h3,.report-section h3{margin-top:0}.chart-row{display:grid;grid-template-columns:minmax(85px,1fr) 2fr 32px;align-items:center;gap:10px;margin:13px 0;font-size:13px}.chart-row b{text-align:right;font-variant-numeric:tabular-nums}.chart-track{height:9px;background:var(--hd-border);border-radius:20px;overflow:hidden}.chart-track i{display:block;height:100%;border-radius:20px;background:var(--hd-accent,#4f7cff)}.report-section{margin:14px 0;padding:18px}.table-responsive{overflow-x:auto}.report-note{font-size:12px;margin-top:18px}.reports-chart-grid-secondary .report-section{margin:0}@media(max-width:800px){.reports-kpis{grid-template-columns:repeat(2,1fr)}.reports-heading{align-items:flex-start;flex-direction:column}.reports-chart-grid{grid-template-columns:1fr}}
</style>

<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js"
        onerror="this.onerror=null;this.src='https://unpkg.com/jspdf@2.5.2/dist/jspdf.umd.min.js'"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.4/dist/jspdf.plugin.autotable.min.js"
        onerror="this.onerror=null;this.src='https://unpkg.com/jspdf-autotable@3.8.4/dist/jspdf.plugin.autotable.min.js'"></script>
<script>
(() => {
    const button = document.getElementById('report-pdf');
    const reportArea = document.getElementById('report-area');
    if (!button || !reportArea) return;

    const chartData = {
        statuses: @json($statusChart),
        priorities: @json($priorityChart),
        agents: @json($agentChart),
        teams: @json($teamChart),
        projects: @json($projectChart),
    };
    const reportName = @json(ucfirst($report));
    const generatedAt = @json(now()->format('d.m.Y H:i'));
    const period = @json(($filters['from'] ?? 'All time').' – '.($filters['to'] ?? 'Today'));
    const kpis = [
        ['Total tickets', @json((string) $total)],
        ['Open workload', @json((string) $open)],
        ['Resolved / closed', @json((string) $resolved)],
        ['Urgent tickets', @json((string) $urgent)],
    ];

    function drawBarChart(pdf, title, items, x, y, width) {
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(11);
        pdf.setTextColor(35, 47, 65);
        pdf.text(title, x, y);
        const rows = items.length ? items.slice(0, 7) : [{label: 'No data', value: 0}];
        const max = Math.max(1, ...rows.map(item => Number(item.value) || 0));
        let rowY = y + 9;
        rows.forEach(item => {
            const label = String(item.label).length > 18 ? String(item.label).slice(0, 16) + '…' : String(item.label);
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(8);
            pdf.setTextColor(80, 90, 105);
            pdf.text(label, x, rowY + 3);
            const barX = x + 43;
            const barWidth = width - 55;
            pdf.setFillColor(232, 237, 245);
            pdf.roundedRect(barX, rowY - 1, barWidth, 5, 2, 2, 'F');
            const value = Number(item.value) || 0;
            if (value > 0) {
                pdf.setFillColor(66, 105, 190);
                pdf.roundedRect(barX, rowY - 1, Math.max(2, barWidth * value / max), 5, 2, 2, 'F');
            }
            pdf.setTextColor(35, 47, 65);
            pdf.text(String(value), x + width, rowY + 3, {align: 'right'});
            rowY += 10;
        });
    }

    button.addEventListener('click', () => {
        if (!window.jspdf?.jsPDF) {
            window.alert('jsPDF could not be loaded. Please check whether your network blocks jsDelivr and unpkg, then reload the page.');
            return;
        }

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4', compress: true });
        if (typeof pdf.autoTable !== 'function') {
            window.alert('The PDF table plugin could not be loaded. Please reload the page and check whether your network blocks jsDelivr and unpkg.');
            return;
        }
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const margin = 14;

        const drawHeader = () => {
            pdf.setFillColor(24, 37, 61);
            pdf.rect(0, 0, pageWidth, 25, 'F');
            pdf.setTextColor(255, 255, 255);
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(15);
            pdf.text('HELPDESK', margin, 11);
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(9);
            pdf.text('MANAGEMENT REPORT', margin, 18);
            pdf.setFontSize(9);
            pdf.text(reportName.toUpperCase(), pageWidth - margin, 14, { align: 'right' });
        };

        const drawFooter = () => {
            const pageCount = pdf.internal.getNumberOfPages();
            for (let page = 1; page <= pageCount; page++) {
                pdf.setPage(page);
                pdf.setDrawColor(220, 225, 232);
                pdf.line(margin, pageHeight - 10, pageWidth - margin, pageHeight - 10);
                pdf.setFont('helvetica', 'normal');
                pdf.setFontSize(8);
                pdf.setTextColor(120, 130, 145);
                pdf.text('Confidential • Generated ' + generatedAt, margin, pageHeight - 5);
                pdf.text('Page ' + page + ' / ' + pageCount, pageWidth - margin, pageHeight - 5, { align: 'right' });
            }
        };

        drawHeader();
        pdf.setTextColor(35, 47, 65);
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(19);
        pdf.text(reportName + ' overview', margin, 39);
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(9);
        pdf.setTextColor(100, 110, 125);
        pdf.text('Period: ' + period + '     •     Generated: ' + generatedAt, margin, 46);

        const cardGap = 5;
        const cardWidth = (pageWidth - margin * 2 - cardGap * 3) / 4;
        kpis.forEach((item, index) => {
            const x = margin + index * (cardWidth + cardGap);
            pdf.setFillColor(245, 247, 251);
            pdf.roundedRect(x, 53, cardWidth, 22, 2, 2, 'F');
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(8);
            pdf.setTextColor(100, 110, 125);
            pdf.text(item[0], x + 5, 61);
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(15);
            pdf.setTextColor(35, 47, 65);
            pdf.text(item[1], x + 5, 70);
        });

        const chartPairs = {
            Executive: [['Tickets by status', chartData.statuses], ['Tickets by priority', chartData.priorities]],
            Tickets: [['Tickets by status', chartData.statuses], ['Tickets by priority', chartData.priorities]],
            Agents: [['Agent workload', chartData.agents], ['Tickets by status', chartData.statuses]],
            Teams: [['Team workload', chartData.teams], ['Tickets by status', chartData.statuses]],
            Projects: [['Tickets by project', chartData.projects], ['Tickets by status', chartData.statuses]],
        };
        const selectedCharts = chartPairs[reportName] || chartPairs.Executive;
        const chartWidth = (pageWidth - margin * 2 - 12) / 2;
        drawBarChart(pdf, selectedCharts[0][0], selectedCharts[0][1], margin, 87, chartWidth);
        drawBarChart(pdf, selectedCharts[1][0], selectedCharts[1][1], margin + chartWidth + 12, 87, chartWidth);

        let tableY = 166;
        const tables = [...reportArea.querySelectorAll('.report-section table')];
        tables.forEach((table) => {
            const section = table.closest('.report-section');
            const heading = section?.querySelector('h3')?.textContent?.trim() || 'Report details';
            if (tableY > pageHeight - 35) {
                pdf.addPage();
                drawHeader();
                tableY = 36;
            }
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(11);
            pdf.setTextColor(35, 47, 65);
            pdf.text(heading, margin, tableY);
            pdf.autoTable({
                html: table,
                startY: tableY + 4,
                margin: { left: margin, right: margin, top: 32, bottom: 16 },
                theme: 'grid',
                showHead: 'everyPage',
                styles: { font: 'helvetica', fontSize: 8, cellPadding: 2.5, textColor: [55, 65, 81], lineColor: [225, 230, 237], lineWidth: 0.15, overflow: 'linebreak' },
                headStyles: { fillColor: [38, 58, 91], textColor: [255, 255, 255], fontStyle: 'bold' },
                alternateRowStyles: { fillColor: [247, 249, 252] },
                didDrawPage: () => drawHeader(),
            });
            tableY = pdf.lastAutoTable.finalY + 13;
        });

        const note = 'Note: SLA compliance and customer satisfaction are not included because the application does not currently collect SLA targets or feedback scores. Tracked hours are not payroll costs.';
        if (tableY > pageHeight - 25) {
            pdf.addPage();
            drawHeader();
            tableY = 38;
        }
        pdf.setFont('helvetica', 'italic');
        pdf.setFontSize(8);
        pdf.setTextColor(105, 115, 130);
        pdf.text(pdf.splitTextToSize(note, pageWidth - margin * 2), margin, tableY);
        drawFooter();
        pdf.save('helpdesk-' + @json($report) + '-report.pdf');
    });
})();
</script>
@endsection
