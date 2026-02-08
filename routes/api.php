<?php

use App\Application\Http\Controllers\API\V1\Auth\AdminRegisterController;
use App\Application\Http\Controllers\API\V1\Auth\LoginController;
use App\Application\Http\Controllers\API\V1\Auth\OtpController;
use App\Application\Http\Controllers\API\V1\Auth\RefreshTokenController;
use App\Application\Http\Controllers\API\V1\Auth\RegisterController;
use App\Application\Http\Controllers\API\V1\ContactUsController;
use App\Application\Http\Controllers\API\V1\StoreCategoryController;
use App\Application\Http\Controllers\API\V1\StoreController;
use App\Application\Http\Controllers\API\V1\UserStoreCategoryController;
use App\Domain\Notification\Models\Notification;
use App\Domain\User\Enums\BudgetCategory;
use App\Domain\User\Enums\InterestingOfferCategory;
use App\Domain\User\Enums\ShoppingStyleCategory;
use App\Domain\User\Models\User;
use App\Application\Http\Controllers\API\V1\NotifyMeController;
use App\Http\Middleware\ContactUsThrottle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::prefix('v1')->group(function () {

    // Authentication
    Route::post('/auth/register', RegisterController::class);
    Route::post('/auth/login', [LoginController::class, 'login']);
    Route::post('/auth/refresh', RefreshTokenController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [LoginController::class, 'logout']);
        Route::get('/auth/me', [LoginController::class, 'me']);
    });

    // Register Admin
    Route::post('/admin/register', AdminRegisterController::class);


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
    Route::post('/contact-us/seller', [ContactUsController::class, 'submit_seller'])->name('contactUs.seller')->middleware([ContactUsThrottle::class]);
    Route::post('/contact-us/customer', [ContactUsController::class, 'submit_customer'])->name('contactUs.customer')->middleware([ContactUsThrottle::class]);

    Route::post('/notify-me/submit', [NotifyMeController::class, 'submit'])->name('notifyMe.submit')->middleware([ContactUsThrottle::class]);
    Route::get('/admin/notify-me/list', [NotifyMeController::class, 'list'])->name('notifyMe.list');
    Route::post('/admin/notify-me/notify-all', [NotifyMeController::class, 'notifyAll'])->name('notifyMe.notifyAll');

    Route::get('/admin/contact-us/customers', [ContactUsController::class, 'index_customer'])->name('contactUs.get.customers');
    Route::get('/admin/contact-us/sellers', [ContactUsController::class, 'index_seller'])->name('contactUs.get.sellers');

    //On Boarding
    Route::middleware(['auth:sanctum'])->group(function () {

        Route::post('/on-boarding/customer', function (Request $request) {
            $data = $request->validate([
                'interesting_offers' => ['required', 'array', 'min:1'],
                'interesting_offers.*' => [
                    'string',
                    Rule::in(InterestingOfferCategory::values()),
                ],

                'shopping_style' => ['required', 'array', 'min:1'],
                'shopping_style.*' => [
                    'string',
                    Rule::in(ShoppingStyleCategory::values()),
                ],

                'budget' => ['required', 'string', Rule::in(BudgetCategory::values())],
            ]);

            DB::transaction(function () use ($data, $request) {

                DB::table('interests')->updateOrInsert(
                    ['user_id' => $request->user()->id],
                    [
                        'interesting_offers' => json_encode($data['interesting_offers']),
                        'shopping_style' => json_encode($data['shopping_style']),
                        'budget' => $data['budget'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Onboarding completed successfully',
            ], 200);
        })->name('onBoarding.customer');
        // Route::post('/on-boarding/seller', function (Request $request) {
        //     $data = $request->validate([
        //         'interesting_offers' => ['required', 'array', 'min:1'],
        //         'interesting_offers.*' => [
        //             'string',
        //             Rule::in(InterestingOfferCategory::values()),
        //         ],

        //         'shopping_style' => ['required', 'array', 'min:1'],
        //         'shopping_style.*' => [
        //             'string',
        //             Rule::in(ShoppingStyleCategory::values()),
        //         ],

        //         'budget' => ['required', 'string', Rule::in(BudgetCategory::values())],
        //     ]);

        //     DB::transaction(function () use ($data, $request) {

        //         DB::table('interests')->updateOrInsert(
        //             ['user_id' => $request->user()->id],
        //             [
        //                 'interesting_offers' => json_encode($data['interesting_offers']),
        //                 'shopping_style' => json_encode($data['shopping_style']),
        //                 'budget' => $data['budget'],
        //                 'updated_at' => now(),
        //                 'created_at' => now(),
        //             ]
        //         );
        //     });

        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Onboarding completed successfully',
        //     ], 200);
        // })->name('onBoarding.seller');

    });

    //StoreCategory
    Route::prefix('admin')->group(function () {
        Route::get('/store-category', [StoreCategoryController::class, 'index'])->name('storeCategory.index');
        Route::post('/store-category', [StoreCategoryController::class, 'store'])->name('storeCategory.store');
        Route::put('/store-category/{category}', [StoreCategoryController::class, 'update'])->name('storeCategory.update'); // بالـ id
        Route::delete('/store-category/{category}', [StoreCategoryController::class, 'destroy'])->name('storeCategory.destroy'); // بالـ id
    });

    Route::get('store-categories', [UserStoreCategoryController::class, 'index'])->name('userStoreCategory.index');
});
