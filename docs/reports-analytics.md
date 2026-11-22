# Reports & Analytics

The Reports module is available to administrators and users granted the reports.view permission.

## Reports
- Executive overview: ticket volume, open workload, resolved/closed share, urgent tickets, status and priority distribution, agent workload, team workload and project health.
- Ticket performance: status and priority distribution.
- Agent performance: assigned workload, resolved/closed tickets and tracked time.
- Team workload: ticket distribution and resolved workload per team.
- Project health: project status, ticket volume, tracked hours, budget metadata and end date.

## Filters and exports
Reports accept date range, team, agent and project filters. CSV exports are streamed in chunks. PDF export uses jsPDF and the AutoTable plugin in the browser to create a landscape PDF from report tables.

## Metric definitions and limitations
- Resolved means status resolved or closed.
- Open workload means any status other than resolved or closed.
- Resolution share is resolved/closed tickets divided by all matching tickets.
- Tracked hours are elapsed_minutes divided by 60.
- Project budget is displayed as configured metadata, not calculated labor cost.
- SLA compliance and customer satisfaction are intentionally omitted until SLA targets and customer feedback scores are collected.
- Agent performance is a workload summary, not a quality or productivity ranking.

## Access
The admin role receives all permissions. Other roles do not receive reports.view by default. Grant it explicitly to an executive or manager role when appropriate.
