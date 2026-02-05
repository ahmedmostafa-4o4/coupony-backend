<?php

use App\Application\Http\Controllers\API\V1\Auth\LoginController;
use App\Application\Http\Controllers\Api\V1\Auth\OtpController;
use App\Application\Http\Controllers\API\V1\Auth\RefreshTokenController;
use App\Application\Http\Controllers\API\V1\Auth\RegisterController;
use App\Application\Http\Controllers\API\V1\ContactUsController;
use App\Application\Http\Controllers\Api\V1\NotificationController;
use App\Application\Http\Controllers\Api\V1\StoreController;
use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use App\Http\Middleware\SellerRoleCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Authentication
    Route::post('/auth/register', RegisterController::class);
    Route::post('/auth/login', [LoginController::class, 'login']);
    Route::post('/auth/refresh', RefreshTokenController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [LoginController::class, 'logout']);
        Route::get('/auth/me', [LoginController::class, 'me']);
    });

    // Notifications
    // Route::get('/notifications', [NotificationController::class, 'index']);
    // Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    // Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    // Route::get('/notifications/{notification}', [NotificationController::class, 'show']);

    // Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    // Route::post('/notifications/{notification}/unread', [NotificationController::class, 'markAsUnread']);
    // Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

    // Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    // Route::delete('/notifications/delete-all-read', [NotificationController::class, 'deleteAllRead']);

    // OTP Management (Public)
    Route::post('/otp/send', [OtpController::class, 'send']);
    Route::post('/otp/verify', [OtpController::class, 'verify']);
    Route::post('/otp/resend', [OtpController::class, 'resend']);

    // Email Verification (Protected)
    // Route::middleware('auth:sanctum')->group(function () {
    //     Route::post('/email/verification-code', [EmailVerificationController::class, 'send']);
    //     Route::post('/email/verify', [EmailVerificationController::class, 'verify']);

    //     Route::post('/phone/verification-code', [PhoneVerificationController::class, 'send']);
    //     Route::post('/phone/verify', [PhoneVerificationController::class, 'verify']);
    // });

    // Store Management
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/store/create', [StoreController::class, 'create'])->name('store.create');
    });

    //Test
    Route::get('/test-mail', function () {
        Mail::to('lofylofy56@gmail.com')->send(
            new \App\Domain\Notification\Mail\NotificationEmail(
                Notification::first(),
                User::first()
            )
        );

        return 'sent';
    });

    Route::get('/mail-check', function () {
        return config('mail.from.address');
    });

    //Web Contact Us
    Route::post('/contact-us/seller', [ContactUsController::class, 'submit_seller'])->middleware('throttle:contact-us-seller');
    Route::post('/contact-us/customer', [ContactUsController::class, 'submit_customer'])->middleware('throttle:contact-us-customer');
});
