<?php

declare(strict_types=1);

namespace Tests\Unit\Insights\Application\Queries\GetDashboardOverview;

use App\Insights\Application\Contracts\DashboardReadRepository;
use App\Insights\Application\Queries\GetDashboardOverview\GetDashboardOverviewHandler;
use App\Insights\Application\Queries\GetDashboardOverview\GetDashboardOverviewQuery;
use PHPUnit\Framework\TestCase;

final class GetDashboardOverviewHandlerTest extends TestCase
{
    public function test_it_builds_the_dashboard_overview_using_the_authenticated_users_scope(): void
    {
        $scope = [
            'user_id' => 42,
            'can_view_all' => false,
            'is_customer' => false,
            'is_agent' => true,
            'is_admin' => false,
        ];
        $data = [
            'statistics' => ['total' => 3, 'open' => 1, 'in_progress' => 1, 'resolved' => 1],
            'priorityBreakdown' => [
                ['key' => 'urgent', 'label' => 'Urgent', 'count' => 1, 'percentage' => 33],
            ],
            'recentTickets' => ['ticket'],
            'recentActivity' => ['activity'],
            'calendarTeams' => ['team'],
        ];

        $repository = $this->createMock(DashboardReadRepository::class);
        $repository->expects(self::once())
            ->method('overview')
            ->with($scope)
            ->willReturn($data);

        $overview = (new GetDashboardOverviewHandler($repository))->handle(
            new GetDashboardOverviewQuery(
                userId: 42,
                canViewAll: false,
                isCustomer: false,
                isAgent: true,
                isAdmin: false,
            ),
        );

        self::assertSame('Your team queue', $overview->audience);
        self::assertSame($data['statistics'], $overview->statistics);
        self::assertSame($data['priorityBreakdown'], $overview->priorityBreakdown);
        self::assertSame($data['recentTickets'], $overview->recentTickets);
        self::assertSame($data['recentActivity'], $overview->recentActivity);
        self::assertSame($data['calendarTeams'], $overview->calendarTeams);
        self::assertSame([
            'statistics' => $data['statistics'],
            'priorityBreakdown' => $data['priorityBreakdown'],
            'recentTickets' => $data['recentTickets'],
            'recentActivity' => $data['recentActivity'],
            'dashboardAudience' => 'Your team queue',
            'calendarTeams' => $data['calendarTeams'],
        ], $overview->toViewData());
    }

    public function test_users_with_global_ticket_permission_receive_workspace_audience(): void
    {
        $repository = $this->createMock(DashboardReadRepository::class);
        $repository->method('overview')->willReturn([
            'statistics' => ['total' => 0, 'open' => 0, 'in_progress' => 0, 'resolved' => 0],
            'priorityBreakdown' => [],
            'recentTickets' => [],
            'recentActivity' => [],
            'calendarTeams' => [],
        ]);

        $overview = (new GetDashboardOverviewHandler($repository))->handle(
            new GetDashboardOverviewQuery(7, true, false, false, true),
        );

        self::assertSame('Workspace overview', $overview->audience);
    }
}
