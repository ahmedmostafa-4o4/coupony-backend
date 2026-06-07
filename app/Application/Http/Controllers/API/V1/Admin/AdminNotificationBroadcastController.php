<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\BroadcastNotificationRequest;
use App\Domain\Notification\Jobs\ProcessNotificationBroadcastJob;
use App\Domain\Notification\Models\NotificationBroadcast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationBroadcastController extends Controller
{
    /**
     * Store and dispatch a new broadcast notification.
     */
    public function store(BroadcastNotificationRequest $request): JsonResponse
    {
        $broadcast = NotificationBroadcast::create([
            'admin_id' => $request->user()->id,
            'title' => $request->validated('title'),
            'message' => $request->validated('message'),
            'channels' => $request->validated('channels'),
            'target_roles' => $request->validated('target_roles'),
            'target_user_ids' => $request->validated('target_user_ids'),
            'status' => 'pending',
        ]);

        // Dispatch background job
        ProcessNotificationBroadcastJob::dispatch($broadcast);

        return response()->json([
            'message' => 'Broadcast notification queued successfully.',
            'broadcast' => $broadcast,
        ], 201);
    }

    /**
     * List past broadcasts.
     */
    public function index(Request $request): JsonResponse
    {
        // Only admins
        if (!$request->user() || !$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $broadcasts = NotificationBroadcast::with(['admin:id,email', 'admin.profile:user_id,first_name,last_name'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return response()->json($broadcasts);
    }

    /**
     * Get specific broadcast details.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        if (!$request->user() || !$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $broadcast = NotificationBroadcast::with(['admin:id,email', 'admin.profile:user_id,first_name,last_name'])
            ->findOrFail($id);

        return response()->json([
            'data' => $broadcast,
        ]);
    }
}
