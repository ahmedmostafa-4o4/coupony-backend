<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Mail\ContactUs;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
    public function index_customer()
    {
        $customers = DB::table('contact_us_customer')->get();
        return response()->json([
            'data' => $customers
        ]);
    }
    public function index_seller()
    {
        $seller = DB::table('contact_us_seller')->get();
        return response()->json([
            'data' => $seller
        ]);
    }
    public function submit_customer(Request $request)
    {
        $ip = $request->ip();
        $key = 'contact-us-customer:' . $ip;
        $attempts = RateLimiter::attempts($key);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
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
            'message' => 'Your message has been sent successfully.',
            'data' => [
                'ip_address' => $ip,
                'attempts' => $attempts,
                'remaining_attempts' => max(0, 3 - $attempts),
            ]
        ], 200);
    }

    public function submit_seller(Request $request)
    {
        $ip = $request->ip();
        $key = 'contact-us-seller:' . $ip;
        $attempts = RateLimiter::attempts($key);

        $validatedData = $request->validate([
            'store_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
        ]);

        DB::table('contact_us_seller')->insert([
            array_merge($validatedData, ['created_at' => now(), 'updated_at' => now()]),
        ]);

        return response()->json([
            'message' => 'Your message has been sent successfully.',
            'data' => [
                'ip_address' => $ip,
                'attempts' => $attempts,
                'remaining_attempts' => max(0, 3 - $attempts),
            ]
        ], 200);
    }
}
