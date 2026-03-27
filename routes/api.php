<?php

use App\Application\Http\Controllers\API\V1\Admin\StoreManagementController;
use App\Application\Http\Controllers\API\V1\Auth\AdminRegisterController;
use App\Application\Http\Controllers\API\V1\Auth\LoginController;
use App\Application\Http\Controllers\API\V1\Auth\OtpController;
use App\Application\Http\Controllers\API\V1\Auth\PasswordResetController;
use App\Application\Http\Controllers\API\V1\Auth\RefreshTokenController;
use App\Application\Http\Controllers\API\V1\Auth\RegisterController;
use App\Application\Http\Controllers\API\V1\ContactUsController;
use App\Application\Http\Controllers\API\V1\LocaleController;
use App\Application\Http\Controllers\API\V1\NotifyMeController;
use App\Application\Http\Controllers\API\V1\OnboardingController;
use App\Application\Http\Controllers\API\V1\SocialController;
use App\Application\Http\Controllers\API\V1\StoreCategoryController;
use App\Application\Http\Controllers\API\V1\StoreController;
use App\Application\Http\Controllers\API\V1\UserStoreCategoryController;
use App\Domain\Notification\Models\Notification;
use App\Domain\User\Enums\BudgetCategory;
use App\Domain\User\Enums\InterestingOfferCategory;
use App\Domain\User\Enums\OffersTypeCategory;
use App\Domain\User\Enums\ShoppingStyleCategory;
use App\Domain\User\Enums\TargetAudienceCategory;
use App\Domain\User\Models\User;
use App\Http\Middleware\ContactUsThrottle;
use App\Http\Middleware\UseAuthenticatedUserLocale;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::get('/locales', [LocaleController::class, 'index'])->name('locales.index');

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        // Public Authentication Routes
        Route::post('/register', RegisterController::class)->name('auth.register');
        Route::post('/login', [LoginController::class, 'login'])->name('auth.login');
        Route::post('/refresh', RefreshTokenController::class)->name('auth.refresh');

        // Password Reset Routes (Public)
        Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword'])->name('password.forgot');
        Route::post('/password/verify-otp', [PasswordResetController::class, 'verifyOtp'])->name('password.verifyOtp');
        Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])->name('password.reset');
        Route::post('/password/resend-otp', [PasswordResetController::class, 'resendOtp'])->name('password.resendOtp');

        // OTP Management (Public)
        Route::post('/otp/send', [OtpController::class, 'send'])->name('otp.send');
        Route::post('/otp/verify', [OtpController::class, 'verify'])->name('otp.verify');
        Route::post('/otp/resend', [OtpController::class, 'resend'])->name('otp.resend');

        // Protected Authentication Routes
        Route::middleware(['auth:sanctum', UseAuthenticatedUserLocale::class])->group(function () {
            Route::post('/logout', [LoginController::class, 'logout'])->name('auth.logout');
            Route::get('/me', [LoginController::class, 'me'])->name('auth.me');
            Route::put('/language', [LocaleController::class, 'update'])->name('auth.language.update');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Store Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', UseAuthenticatedUserLocale::class])->group(function () {
        Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');
        Route::post('/stores/{store}/verification-document', [StoreController::class, 'updateVerificationDocument'])->name('stores.updateVerificationDocument');
    });

    // Public Store Categories
    Route::get('/store-categories', [UserStoreCategoryController::class, 'index'])->name('userStoreCategory.index');
    Route::get('/socials', [SocialController::class, 'index'])->name('socials.index');

    /*
    |--------------------------------------------------------------------------
    | Onboarding Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', UseAuthenticatedUserLocale::class])->group(function () {
        Route::get('/on-boarding/customer', [OnboardingController::class, 'customer'])->name('onBoarding.customer.show');
        Route::post('/on-boarding/customer', [OnboardingController::class, 'storeCustomer'])->name('onBoarding.customer');

        Route::get('/on-boarding/seller', [OnboardingController::class, 'seller'])->name('onBoarding.seller.show');
        Route::post('/on-boarding/seller', [OnboardingController::class, 'storeSeller'])->name('onBoarding.seller');
    });

    /*
    |--------------------------------------------------------------------------
    | Contact Us & Notify Me Routes
    |--------------------------------------------------------------------------
    */
    Route::post('/contact-us/seller', [ContactUsController::class, 'submit_seller'])
        ->name('contactUs.seller')
        ->middleware(ContactUsThrottle::class);

    Route::post('/contact-us/customer', [ContactUsController::class, 'submit_customer'])
        ->name('contactUs.customer')
        ->middleware(ContactUsThrottle::class);

    Route::post('/notify-me/submit', [NotifyMeController::class, 'submit'])
        ->name('notifyMe.submit')
        ->middleware(ContactUsThrottle::class);

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::post('/admin/register', AdminRegisterController::class)->name('admin.register');

    Route::prefix('admin')->middleware(['auth:sanctum', UseAuthenticatedUserLocale::class, 'role:admin'])->group(function () {

        // Store Categories Management
        Route::prefix('store-category')->name('storeCategory.')->group(function () {
            Route::get('/', [StoreCategoryController::class, 'index'])->name('index');
            Route::post('/', [StoreCategoryController::class, 'store'])->name('store');
            Route::put('/{category}', [StoreCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [StoreCategoryController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('socials')->name('socials.')->group(function () {
            Route::post('/', [SocialController::class, 'store'])->name('store');
            Route::put('/{social}', [SocialController::class, 'update'])->name('update');
            Route::delete('/{social}', [SocialController::class, 'destroy'])->name('destroy');
        });

        // Store Management
        Route::prefix('stores')->name('admin.stores.')->group(function () {
            Route::get('/', [StoreManagementController::class, 'index'])->name('index');
            Route::get('/pending', [StoreManagementController::class, 'pending'])->name('pending');
            Route::get('/statistics', [StoreManagementController::class, 'statistics'])->name('statistics');
            Route::get('/{store}', [StoreManagementController::class, 'show'])->name('show');
            Route::post('/{store}/approve', [StoreManagementController::class, 'approve'])->name('approve');
            Route::post('/{store}/reject', [StoreManagementController::class, 'reject'])->name('reject');

            // Verification Documents
            Route::get('/{store}/verifications', [StoreManagementController::class, 'verificationDocuments'])->name('verifications');
            Route::post('/{store}/verifications/{verification}/approve', [StoreManagementController::class, 'approveDocument'])->name('verifications.approve');
            Route::post('/{store}/verifications/{verification}/reject', [StoreManagementController::class, 'rejectDocument'])->name('verifications.reject');
        });

        // Contact Us Management
        Route::prefix('contact-us')->name('contactUs.get.')->group(function () {
            Route::get('/customers', [ContactUsController::class, 'index_customer'])->name('customers');
            Route::get('/sellers', [ContactUsController::class, 'index_seller'])->name('sellers');
        });

        // Notify Me Management
        Route::prefix('notify-me')->name('notifyMe.')->group(function () {
            Route::get('/list', [NotifyMeController::class, 'list'])->name('list');
            Route::post('/notify-all', [NotifyMeController::class, 'notifyAll'])->name('notifyAll');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Development/Testing Routes
    |--------------------------------------------------------------------------
    */
    if (app()->environment('local', 'development')) {
        Route::get('/test-mail', function () {
            Mail::to('lofylofy56@gmail.com')->send(
                new \App\Domain\Notification\Mail\NotificationEmail(
                    Notification::first(),
                    User::first()
                )
            );
            return 'sent';
        })->name('test.mail');

        Route::get('/mail-check', function () {
            return config('mail.from.address');
        })->name('test.mailCheck');
    }



    Route::get('/log-test', function () {
        Log::info('test from hostinger');
        return 'done';
    });
});
