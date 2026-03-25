<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\NotificationResource;
use App\Domain\Notification\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
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

        $user = $request->user();

        $notifications = $user->notifications()
            ->unread()
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Get single notification.
     */
    public function show(Request $request, Notification $notification): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => __('api.common.unauthorized'),
            ], 403);
        }

        return response()->json([
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => __('api.common.unauthorized'),
            ], 403);
        }

        $notification->markAsRead();

        return response()->json([
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

        return response()->json([
            'message' => __('api.notifications.marked_all_as_read', ['count' => $updated]),
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }

    /**
     * Mark as unread.
     */
    public function markAsUnread(Request $request, Notification $notification): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => __('api.common.unauthorized'),
            ], 403);
        }

        $notification->markAsUnread();

        return response()->json([
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

        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'message' => __('api.common.unauthorized'),
            ], 403);
        }

        $notification->delete();

        return response()->json([
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

        return response()->json([
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

        return response()->json([
            'data' => [
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }
}
