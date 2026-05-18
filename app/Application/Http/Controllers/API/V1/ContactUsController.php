<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Mail\ContactUs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactUsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            // examples with aliases, pipe-separated names, guards, etc:
            new Middleware(\Spatie\Permission\Middleware\RoleMiddleware::using('admin'), except: ['submit_customer', 'submit_seller']),
            new Middleware('auth:sanctum', except: ['submit_customer', 'submit_seller']),
        ];
    }

    public function index_customer(Request $request)
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $customers = DB::table('contact_us_customer')->orderBy('created_at', 'desc')->get();

            return response()->json([
                'message' => __('api.contact.customer_contacts_retrieved'),
                'data' => $customers,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve customer contacts', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => __('api.contact.customer_contacts_failed'),
            ], 500);
        }
    }

    public function index_seller(Request $request)
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $sellers = DB::table('contact_us_seller')->orderBy('created_at', 'desc')->get();

            return response()->json([
                'message' => __('api.contact.seller_contacts_retrieved'),
                'data' => $sellers,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve seller contacts', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => __('api.contact.seller_contacts_failed'),
            ], 500);
        }
    }

    public function submit_customer(Request $request)
    {
        try {
            $ip = $request->ip();
            $key = 'contact-us-customer:'.$ip;
            $attempts = RateLimiter::attempts($key);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string|max:1000',
            ]);

            DB::table('contact_us_customer')->insert([
                array_merge($validatedData, ['created_at' => now(), 'updated_at' => now()]),
            ]);

            Mail::to('info@coupony.shop')
                ->queue(new ContactUs([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'message' => $request->message,
                ]));

            return response()->json([
                'message' => __('api.contact.customer_submitted'),
                'data' => [
                    'ip_address' => $ip,
                    'attempts' => $attempts,
                    'remaining_attempts' => max(0, 3 - $attempts),
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => __('api.common.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to submit customer contact', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => __('api.contact.customer_submit_failed'),
            ], 500);
        }
    }

    public function submit_seller(Request $request)
    {
        try {
            $ip = $request->ip();
            $key = 'contact-us-seller:'.$ip;
            $attempts = RateLimiter::attempts($key);

            $validatedData = $request->validate([
                'store_name' => 'nullable|string|max:255',
                'phone_number' => 'required|string|max:20',
            ]);

            $validatedData['store_name'] = $validatedData['store_name'] ?? 'N/A';

            DB::table('contact_us_seller')->insert([
                array_merge($validatedData, ['created_at' => now(), 'updated_at' => now()]),
            ]);

            return response()->json([
                'message' => __('api.contact.seller_submitted'),
                'data' => [
                    'ip_address' => $ip,
                    'attempts' => $attempts,
                    'remaining_attempts' => max(0, 3 - $attempts),
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => __('api.common.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to submit seller contact', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => __('api.contact.seller_submit_failed'),
            ], 500);
        }
    }
}
