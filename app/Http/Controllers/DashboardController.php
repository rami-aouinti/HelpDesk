<?php

namespace App\Http\Controllers;

use App\Insights\Application\Queries\GetDashboardOverview\GetDashboardOverviewHandler;
use App\Insights\Application\Queries\GetDashboardOverview\GetDashboardOverviewQuery;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(GetDashboardOverviewHandler $handler): View
    {
        $user = auth()->user();

        $overview = $handler->handle(new GetDashboardOverviewQuery(
            userId: (int) $user->id,
            canViewAll: $user->can('tickets.view.all'),
            isCustomer: $user->hasRole('customer'),
            isAgent: $user->hasRole('agent'),
            isAdmin: $user->hasRole('admin'),
        ));

        return view('dashboard.index', $overview->toViewData());
    }
}
