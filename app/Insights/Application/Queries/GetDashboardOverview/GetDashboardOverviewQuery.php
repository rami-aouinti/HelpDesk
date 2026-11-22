<?php

declare(strict_types=1);

namespace App\Insights\Application\Queries\GetDashboardOverview;

final readonly class GetDashboardOverviewQuery
{
    public function __construct(
        public int $userId,
        public bool $canViewAll,
        public bool $isCustomer,
        public bool $isAgent,
        public bool $isAdmin,
    ) {
    }

    /**
     * @return array{user_id: int, can_view_all: bool, is_customer: bool, is_agent: bool, is_admin: bool}
     */
    public function scope(): array
    {
        return [
            'user_id' => $this->userId,
            'can_view_all' => $this->canViewAll,
            'is_customer' => $this->isCustomer,
            'is_agent' => $this->isAgent,
            'is_admin' => $this->isAdmin,
        ];
    }
}
