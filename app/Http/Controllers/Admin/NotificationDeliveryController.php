<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Communications\Application\Queries\GetNotificationDeliveryOverview\GetNotificationDeliveryOverviewHandler;
use App\Communications\Application\Queries\GetNotificationDeliveryOverview\GetNotificationDeliveryOverviewQuery;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class NotificationDeliveryController extends Controller
{
    public function index(Request $request, GetNotificationDeliveryOverviewHandler $handler): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'sent', 'failed'])],
            'channel' => ['nullable', Rule::in(['mail', 'database', 'broadcast'])],
            'search' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $data = $handler->handle(new GetNotificationDeliveryOverviewQuery(
            filters: $filters,
            perPage: (int) $request->query('per_page', 20),
        ));

        return view('admin.communications.deliveries.index', [
            'deliveries' => $data['deliveries'],
            'summary' => $data['summary'],
            'filters' => $filters,
        ]);
    }
}
