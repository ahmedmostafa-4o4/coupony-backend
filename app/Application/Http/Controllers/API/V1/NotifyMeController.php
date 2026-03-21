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
    ) {

    }
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
                    'message' => 'This email is already registered for notifications.',
                ], 400);
            }

            DB::table('notify_me')->insert([
                array_merge($data, ['created_at' => now(), 'updated_at' => now()]),
            ]);

            return response()->json([
                'message' => 'Thank you! We will notify you when new stores are available.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to submit notify me request', ['error' => $e->getMessage()]);
            
            return response()->json([
                'message' => 'Failed to register for notifications. Please try again later.',
            ], 500);
        }
    }

    public function list()
    {
        try {
            $data = DB::table('notify_me')->orderBy('created_at', 'desc')->get();

            return response()->json([
                'message' => 'Notify me requests retrieved successfully.',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve notify me requests', ['error' => $e->getMessage()]);
            
            return response()->json([
                'message' => 'Unable to retrieve notify me requests. Please try again later.',
            ], 500);
        }
    }

    public function notifyAll(Request $request)
    {
        try {
            $data = $request->validate([
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
            ]);
            
            $result = $this->notificationService->notifyAll($data);
            
            return response()->json([
                'message' => 'Notifications sent successfully.',
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to send notifications', ['error' => $e->getMessage()]);
            
            return response()->json([
                'message' => 'Failed to send notifications. Please try again later.',
            ], 500);
        }
    }
}
