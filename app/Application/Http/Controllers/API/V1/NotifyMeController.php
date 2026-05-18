<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Domain\Notification\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifyMeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            // examples with aliases, pipe-separated names, guards, etc:
            new Middleware(\Spatie\Permission\Middleware\RoleMiddleware::using('admin'), except: ['submit']),
            new Middleware('auth:sanctum', except: ['submit']),
        ];
    }

    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function submit(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => 'required|email|max:255',
            ]);

            // Check if email already exists
            $exists = DB::table('notify_me')->where('email', $data['email'])->exists();
            if ($exists) {
                return response()->json([
                    'message' => __('api.notify_me.already_registered'),
                ], 400);
            }

            DB::table('notify_me')->insert([
                array_merge($data, ['created_at' => now(), 'updated_at' => now()]),
            ]);

            return response()->json([
                'message' => __('api.notify_me.submitted'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => __('api.common.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to submit notify me request', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => __('api.notify_me.submit_failed'),
            ], 500);
        }
    }

    public function list(Request $request)
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $data = DB::table('notify_me')->orderBy('created_at', 'desc')->get();

            return response()->json([
                'message' => __('api.notify_me.list_retrieved'),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve notify me requests', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => __('api.notify_me.list_failed'),
            ], 500);
        }
    }

    public function notifyAll(Request $request)
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $data = $request->validate([
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
            ]);

            $result = $this->notificationService->notifyAll($data);

            return response()->json([
                'message' => __('api.notify_me.notifications_sent'),
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => __('api.common.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to send notifications', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => __('api.notify_me.notifications_failed'),
            ], 500);
        }
    }
}
