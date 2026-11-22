<?php

namespace App\Http\Controllers;

use App\Communications\Application\Queries\GetNotificationInbox\GetNotificationInboxHandler;
use App\Communications\Application\Queries\GetNotificationInbox\GetNotificationInboxQuery;
use App\Communications\Application\Queries\GetRecentNotifications\GetRecentNotificationsHandler;
use App\Communications\Application\Queries\GetRecentNotifications\GetRecentNotificationsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, GetNotificationInboxHandler $handler): View
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $data = $handler->handle(new GetNotificationInboxQuery(
            userId: (int) $request->user()->id,
            unreadOnly: $filter === 'unread',
        ));

        return view('notifications.index', [
            'notifications' => $data['notifications'],
            'unreadCount' => $data['unread_count'],
            'filter' => $filter,
        ]);
    }

    public function recent(Request $request, GetRecentNotificationsHandler $handler): JsonResponse
    {
        $data = $handler->handle(new GetRecentNotificationsQuery(
            userId: (int) $request->user()->id,
        ));

        return response()->json([
            'unread_count' => $data['unread_count'],
            'notifications' => $data['notifications']->map(
                fn ($notification): array => [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'HelpDesk notification',
                    'message' => $notification->data['message'] ?? '',
                    'ticket_subject' => $notification->data['ticket_subject'] ?? null,
                    'url' => $notification->data['url'] ?? route('tickets.index'),
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->diffForHumans(),
                ],
            ),
        ]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }
}
