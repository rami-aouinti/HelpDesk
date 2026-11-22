<?php

declare(strict_types=1);

namespace App\Insights\Application\DTO;

final readonly class DashboardOverview
{
    /**
     * @param array<string, int> $statistics
     * @param array<int, array{key: string, label: string, count: int, percentage: int}> $priorityBreakdown
     */
    public function __construct(
        public array $statistics,
        public array $priorityBreakdown,
        public mixed $recentTickets,
        public mixed $recentActivity,
        public string $audience,
        public mixed $calendarTeams,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toViewData(): array
    {
        return [
            'statistics' => $this->statistics,
            'priorityBreakdown' => $this->priorityBreakdown,
            'recentTickets' => $this->recentTickets,
            'recentActivity' => $this->recentActivity,
            'dashboardAudience' => $this->audience,
            'calendarTeams' => $this->calendarTeams,
        ];
    }
}
