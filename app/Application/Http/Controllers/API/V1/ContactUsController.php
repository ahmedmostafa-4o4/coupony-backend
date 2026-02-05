<?php

namespace App\Application\Http\Controllers\API\V1;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ContactUsController
{
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

        RateLimiter::hit($key, 60); // 60 seconds window

        return response()->json([
            'message' => 'Your message has been sent successfully.',
            'data' => [
                'ip_address' => $ip,
                'attempts' => $attempts + 1,
                'remaining_attempts' => max(0, 3 - $attempts - 1),
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

        RateLimiter::hit($key, 60); // 60 seconds window

        return response()->json([
            'message' => 'Your message has been sent successfully.',
            'data' => [
                'ip_address' => $ip,
                'attempts' => $attempts + 1,
                'remaining_attempts' => max(0, 3 - $attempts - 1),
            ]
        ], 200);
    }
}
