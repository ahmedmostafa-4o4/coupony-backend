<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\NotificationResource;
use App\Domain\Notification\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $perPage = $validated['per_page'] ?? 20;

        $notifications = $user->notifications()
            ->latest()
            ->paginate($perPage);

        return $this->localizedJson([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * Get unread notifications.
     */
    public function unread(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $perPage = $validated['per_page'] ?? 20;

        $notifications = $user->notifications()
            ->unread()
            ->latest()
            ->paginate($perPage);

        return $this->localizedJson([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * Get single notification.
     */
    public function show(Request $request, Notification $notification): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->userOwnsNotification($request, $notification)) {
            return $this->localizedJson([
                'message' => __('api.common.unauthorized'),
            ], 403);
        }

        return $this->localizedJson([
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->userOwnsNotification($request, $notification)) {
            return $this->localizedJson([
                'message' => __('api.common.unauthorized'),
            ], 403);
        }

        $notification->markAsRead();

        return $this->localizedJson([
            'message' => __('api.notifications.marked_as_read'),
            'data' => new NotificationResource($notification->fresh()),
        ]);
    }

    /**
     * Mark all as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();

        $updated = $user->notifications()
            ->unread()
            ->update(['read_at' => now()]);

        return $this->localizedJson([
            'message' => __('api.notifications.marked_all_as_read', ['count' => $updated]),
            'data' => [
                'updated_count' => $updated,
                'unread_count' => 0,
            ],
        ]);
    }

    /**
     * Mark as unread.
     */
    public function markAsUnread(Request $request, Notification $notification): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->userOwnsNotification($request, $notification)) {
            return $this->localizedJson([
                'message' => __('api.common.unauthorized'),
            ], 403);
        }

        $notification->markAsUnread();

        return $this->localizedJson([
            'message' => __('api.notifications.marked_as_unread'),
            'data' => new NotificationResource($notification->fresh()),
        ]);
    }

    /**
     * Delete notification.
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if (! $this->userOwnsNotification($request, $notification)) {
            return $this->localizedJson([
                'message' => __('api.common.unauthorized'),
            ], 403);
        }

        $notification->delete();

        return $this->localizedJson([
            'message' => __('api.notifications.deleted'),
        ], 200);
    }

    /**
     * Delete all read notifications.
     */
    public function deleteAllRead(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();

        $deleted = $user->notifications()
            ->read()
            ->delete();

        return $this->localizedJson([
            'message' => __('api.notifications.deleted_read', ['count' => $deleted]),
            'data' => [
                'deleted_count' => $deleted,
            ],
        ]);
    }

    /**
     * Get unread count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();

        return $this->localizedJson([
            'data' => [
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    private function userOwnsNotification(Request $request, Notification $notification): bool
    {
        return (string) $notification->user_id === (string) $request->user()->id;
    }
}
