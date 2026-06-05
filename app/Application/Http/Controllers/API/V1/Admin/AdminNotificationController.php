<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Domain\Notification\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $query = $request->user()->notifications()->latest();

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        $notifications = $query->paginate($request->integer('per_page', 20));

        return $this->localizedJson([
            'message' => 'Notifications retrieved successfully.',
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $notificationIds = $request->input('notification_ids', []);

        $query = $request->user()->unreadNotifications();

        if (!empty($notificationIds) && is_array($notificationIds)) {
            $query->whereIn('id', $notificationIds);
        }

        $query->update(['read_at' => now()]);

        return $this->localizedJson([
            'message' => 'Notifications marked as read successfully.',
            'data' => [
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]
        ]);
    }
}
