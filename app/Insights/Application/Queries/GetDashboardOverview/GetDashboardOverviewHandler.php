<?php

namespace App\Insights\Application\Queries\GetDashboardOverview;

use App\Insights\Application\Contracts\DashboardReadRepository;
use App\Insights\Application\DTO\DashboardOverview;

final readonly class GetDashboardOverviewHandler
{
    public function __construct(private DashboardReadRepository $dashboard)
    {
    }

    public function handle(GetDashboardOverviewQuery $query): DashboardOverview
    {
        $data = $this->dashboard->overview($query->scope());

        $audience = match (true) {
            $query->canViewAll => 'Workspace overview',
            $query->isAgent => 'Your team queue',
            $query->isCustomer => 'Your support requests',
            default => 'Your workspace',
        };

        return new DashboardOverview(
            statistics: $data['statistics'],
            priorityBreakdown: $data['priorityBreakdown'],
            recentTickets: $data['recentTickets'],
            recentActivity: $data['recentActivity'],
            audience: $audience,
            calendarTeams: $data['calendarTeams'],
        );
    }
}
