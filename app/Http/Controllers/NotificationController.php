<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->limit(8)->get()->map(
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
