<?php

namespace App\Insights\Application\Contracts;

interface DashboardReadRepository
{
    /**
     * @param array{user_id: int, can_view_all: bool, is_customer: bool, is_agent: bool, is_admin: bool} $scope
     * @return array<string, mixed>
     */
    public function overview(array $scope): array;
}
