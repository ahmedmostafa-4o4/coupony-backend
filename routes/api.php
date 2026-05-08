<?php

use App\Application\Http\Controllers\API\V1\Admin\StoreManagementController;
use App\Application\Http\Controllers\API\V1\Admin\ProductManagementController;
use App\Application\Http\Controllers\API\V1\Admin\ProductRevisionManagementController;
use App\Application\Http\Controllers\API\V1\Admin\UserManagementController;
use App\Application\Http\Controllers\API\V1\Auth\AdminRegisterController;
use App\Application\Http\Controllers\API\V1\Auth\GoogleLoginController;
use App\Application\Http\Controllers\API\V1\Auth\LoginController;
use App\Application\Http\Controllers\API\V1\Auth\OtpController;
use App\Application\Http\Controllers\API\V1\Auth\PasswordResetController;
use App\Application\Http\Controllers\API\V1\Auth\RefreshTokenController;
use App\Application\Http\Controllers\API\V1\Auth\RegisterController;
use App\Application\Http\Controllers\API\V1\ContactUsController;
use App\Application\Http\Controllers\API\V1\LocaleController;
use App\Application\Http\Controllers\API\V1\MeAddressController;
use App\Application\Http\Controllers\API\V1\NotifyMeController;
use App\Application\Http\Controllers\API\V1\OnboardingController;
use App\Application\Http\Controllers\API\V1\OfferClaimController;
use App\Application\Http\Controllers\API\V1\CategoryController;
use App\Application\Http\Controllers\API\V1\ProductImageController;
use App\Application\Http\Controllers\API\V1\ProductLikeController;
use App\Application\Http\Controllers\API\V1\ProductCommentController;
use App\Application\Http\Controllers\API\V1\ProductCommentLikeController;
use App\Application\Http\Controllers\API\V1\ProductController;
use App\Application\Http\Controllers\API\V1\ProductRecommendationController;
use App\Application\Http\Controllers\API\V1\ProductRevisionController;
use App\Application\Http\Controllers\API\V1\ProductVariantController;
use App\Application\Http\Controllers\API\V1\SocialController;
use App\Application\Http\Controllers\API\V1\StoreOfferClaimController;
use App\Application\Http\Controllers\API\V1\StoreAddressController;
use App\Application\Http\Controllers\API\V1\StoreCategoryController;
use App\Application\Http\Controllers\API\V1\StoreCommentController;
use App\Application\Http\Controllers\API\V1\StoreCommentLikeController;
use App\Application\Http\Controllers\API\V1\StoreController;
use App\Application\Http\Controllers\API\V1\StoreFollowController;
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
    Route::get('/products', [ProductController::class, 'publicIndex'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'publicShow'])->name('products.show');
    Route::get('/products/{product}/comments', [ProductCommentController::class, 'index'])->name('products.comments.index');
    // Route::get('/stores', [StoreController::class, 'publicIndex'])->name('stores.index');
    Route::get('/public-stores', [StoreController::class, 'publicIndex'])->name('public.stores.index');
    Route::get('/public-stores/{store}/products', [ProductController::class, 'publicStoreIndex'])->name('public.stores.products.index');
    Route::get('/public-stores/{store}/comments', [StoreCommentController::class, 'index'])->name('public.stores.comments.index');
    Route::get('/categories', [ProductController::class, 'categories'])->name('categories.index');
    Route::get('/categories/{category}/products', [ProductController::class, 'categoryProducts'])->name('categories.products.index');

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        // Public Authentication Routes
        Route::post('/register', RegisterController::class)->name('auth.register');
        Route::post('/login', [LoginController::class, 'login'])->name('auth.login');
        Route::post('/google', GoogleLoginController::class)->name('auth.google');
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
            Route::post('/change-password', [LoginController::class, 'changePassword'])->name('auth.change-password');
            Route::patch('/me', [LoginController::class, 'updateMe'])->name('me.update');
            Route::delete('/me', [LoginController::class, 'destroyMe'])->name('me.destroy');
            Route::put('/language', [LocaleController::class, 'update'])->name('auth.language.update');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Store Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', UseAuthenticatedUserLocale::class])->group(function () {
        Route::post('/products/{product}/claims', [OfferClaimController::class, 'store'])->name('products.claims.store');
        Route::post('/products/{product}/likes', [ProductLikeController::class, 'store'])->name('products.likes.store');
        Route::delete('/products/{product}/likes', [ProductLikeController::class, 'destroy'])->name('products.likes.destroy');
        Route::post('/products/{product}/comments', [ProductCommentController::class, 'store'])->name('products.comments.store');
        Route::post('/products/{product}/comments/{comment}/replies', [ProductCommentController::class, 'reply'])->name('products.comments.replies.store');
        Route::patch('/product-comments/{comment}', [ProductCommentController::class, 'update'])->name('product-comments.update');
        Route::delete('/product-comments/{comment}', [ProductCommentController::class, 'destroy'])->name('product-comments.destroy');
        Route::post('/product-comments/{comment}/likes', [ProductCommentLikeController::class, 'store'])->name('product-comments.likes.store');
        Route::delete('/product-comments/{comment}/likes', [ProductCommentLikeController::class, 'destroy'])->name('product-comments.likes.destroy');
        Route::patch('/product-comments/{comment}/hide', [ProductCommentController::class, 'hide'])->name('product-comments.hide');
        Route::post('/public-stores/{store}/comments', [StoreCommentController::class, 'store'])->name('public.stores.comments.store');
        Route::post('/public-stores/{store}/comments/{comment}/replies', [StoreCommentController::class, 'reply'])->name('public.stores.comments.replies.store');
        Route::patch('/store-comments/{comment}', [StoreCommentController::class, 'update'])->name('store-comments.update');
        Route::delete('/store-comments/{comment}', [StoreCommentController::class, 'destroy'])->name('store-comments.destroy');
        Route::post('/store-comments/{comment}/likes', [StoreCommentLikeController::class, 'store'])->name('store-comments.likes.store');
        Route::delete('/store-comments/{comment}/likes', [StoreCommentLikeController::class, 'destroy'])->name('store-comments.likes.destroy');
        Route::patch('/store-comments/{comment}/hide', [StoreCommentController::class, 'hide'])->name('store-comments.hide');
        Route::post('/public-stores/{store}/follow', [StoreFollowController::class, 'store'])->name('public.stores.follow.store');
        Route::delete('/public-stores/{store}/follow', [StoreFollowController::class, 'destroy'])->name('public.stores.follow.destroy');
        Route::patch('/public-stores/{store}/follow/notifications', [StoreFollowController::class, 'toggleNotifications'])->name('public.stores.follow.notifications');
        Route::get('/public-stores/{store}/followers', [StoreFollowController::class, 'getFollowers'])->name('public.stores.followers.index');
        Route::post('/stores', [StoreController::class, 'store'])->name('store.create');
        Route::get('/stores', [StoreController::class, 'index'])->name('me.stores.index');
        Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');
        Route::patch('/stores/{store}/profile', [StoreController::class, 'updateProfile'])->name('stores.profile.update');
        Route::post('/stores/{store}/verification-document', [StoreController::class, 'updateVerificationDocument'])->name('stores.updateVerificationDocument');
        Route::scopeBindings()->group(function () {
            Route::prefix('/stores/{store}/addresses')->name('stores.addresses.')->group(function () {
                Route::get('/', [StoreAddressController::class, 'index'])->name('index');
                Route::post('/', [StoreAddressController::class, 'store'])->name('store');
                Route::get('/{address}', [StoreAddressController::class, 'show'])->name('show');
                Route::patch('/{address}', [StoreAddressController::class, 'update'])->name('update');
                Route::delete('/{address}', [StoreAddressController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('/stores/{store}/products')->name('stores.products.')->group(function () {
                Route::post('/', [ProductController::class, 'store'])->name('store');
                Route::get('/{product}', [ProductController::class, 'show'])->name('show');
                Route::put('/{product}', [ProductController::class, 'update'])->name('update');
                Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
                Route::patch('/{product}/status', [ProductController::class, 'updateStatus'])->name('status');
                Route::get('/{product}/revisions', [ProductRevisionController::class, 'index'])->name('revisions.index');
                Route::get('/{product}/revisions/{revision}', [ProductRevisionController::class, 'show'])->name('revisions.show');
                Route::get('/{product}/variants', [ProductVariantController::class, 'index'])->name('variants.index');
                Route::post('/{product}/variants', [ProductVariantController::class, 'store'])->name('variants.store');
                Route::get('/{product}/variants/{variant}', [ProductVariantController::class, 'show'])->name('variants.show');
                Route::put('/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
                Route::delete('/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy');
                Route::put('/{product}/variants/{variant}/attributes', [ProductVariantController::class, 'replaceAttributes'])->name('variants.attributes.update');
                Route::get('/{product}/images', [ProductImageController::class, 'index'])->name('images.index');
                Route::post('/{product}/images', [ProductImageController::class, 'store'])->name('images.store');
                Route::patch('/{product}/images/reorder', [ProductImageController::class, 'reorder'])->name('images.reorder');
                Route::patch('/{product}/images/{image}/primary', [ProductImageController::class, 'setPrimary'])->name('images.primary');
                Route::delete('/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('images.destroy');
            });
            Route::prefix('/stores/{store}/products')->name('me.stores.products.')->group(function () {
                Route::get('/', [ProductController::class, 'sellerIndex'])->name('index');
            });
            Route::prefix('/me/stores/{store}/products')->name('me.stores.products.')->group(function () {
                Route::get('/', [ProductController::class, 'sellerIndex'])->name('legacy-index');
            });
            Route::prefix('/stores/{store}/offer-claims')
                ->middleware('role:store_employee')
                ->name('stores.offer-claims.')
                ->group(function () {
                    Route::get('/', [StoreOfferClaimController::class, 'index'])->name('index');
                    Route::get('/{claim}', [StoreOfferClaimController::class, 'show'])->name('show');
                    Route::post('/redeem', [StoreOfferClaimController::class, 'redeem'])->name('redeem');
                });
            Route::prefix('/stores/{store}/invitations')->name('stores.invitations.')->group(function () {
                Route::post('/', [\App\Application\Http\Controllers\API\V1\StoreInvitationController::class, 'store'])->name('store');
                Route::get('/', [\App\Application\Http\Controllers\API\V1\StoreInvitationController::class, 'index'])->name('index');
                Route::delete('/{invitation}', [\App\Application\Http\Controllers\API\V1\StoreInvitationController::class, 'destroy'])->name('destroy');
                Route::post('/{invitation}/resend', [\App\Application\Http\Controllers\API\V1\StoreInvitationController::class, 'resend'])->name('resend');
            });
            Route::prefix('/stores/{store}/employees')->name('stores.employees.')->group(function () {
                Route::get('/', [\App\Application\Http\Controllers\API\V1\StoreInvitationController::class, 'employees'])->name('index');
                Route::delete('/{user}', [\App\Application\Http\Controllers\API\V1\StoreInvitationController::class, 'removeEmployee'])->name('destroy');
            });
        });
        Route::post('/invitations/{invitation}/accept', [\App\Application\Http\Controllers\API\V1\StoreInvitationController::class, 'accept'])->name('invitations.accept');
        Route::post('/invitations/{invitation}/decline', [\App\Application\Http\Controllers\API\V1\StoreInvitationController::class, 'decline'])->name('invitations.decline');
        Route::get('/me/invitations', [\App\Application\Http\Controllers\API\V1\StoreInvitationController::class, 'myInvitations'])->name('me.invitations');
        Route::get('/me/addresses', [MeAddressController::class, 'index'])->name('me.addresses.index');
        Route::post('/me/addresses', [MeAddressController::class, 'store'])->name('me.addresses.store');
        Route::patch('/me/addresses/{addressId}', [MeAddressController::class, 'update'])->name('me.addresses.update');
        Route::delete('/me/addresses/{addressId}', [MeAddressController::class, 'destroy'])->name('me.addresses.destroy');
        Route::get('/me/liked-products', [ProductLikeController::class, 'index'])->name('me.products.likes.index');
        Route::get('/me/recommendations/products', [ProductRecommendationController::class, 'index'])->name('me.products.recommendations.index');
        Route::get('/me/followed-stores', [StoreFollowController::class, 'index'])->name('me.followed-stores.index');
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
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        });

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
            Route::get('/suspended', [StoreManagementController::class, 'suspended'])->name('suspended');
            Route::get('/closed', [StoreManagementController::class, 'closed'])->name('closed');
            Route::get('/statistics', [StoreManagementController::class, 'statistics'])->name('statistics');
            Route::get('/{store}', [StoreManagementController::class, 'show'])->name('show');
            Route::post('/{store}/approve', [StoreManagementController::class, 'approve'])->name('approve');
            Route::post('/{store}/reject', [StoreManagementController::class, 'reject'])->name('reject');
            Route::post('/{store}/suspend', [StoreManagementController::class, 'suspend'])->name('suspend');
            Route::post('/{store}/close', [StoreManagementController::class, 'close'])->name('close');

            // Verification Documents
            Route::get('/{store}/verifications', [StoreManagementController::class, 'verificationDocuments'])->name('verifications');
            Route::post('/{store}/verifications/{verification}/approve', [StoreManagementController::class, 'approveDocument'])->name('verifications.approve');
            Route::post('/{store}/verifications/{verification}/reject', [StoreManagementController::class, 'rejectDocument'])->name('verifications.reject');
        });

        Route::prefix('products')->name('admin.products.')->group(function () {
            Route::get('/pending', [ProductRevisionManagementController::class, 'pending'])->name('pending');
            Route::get('/revisions/{revision}', [ProductRevisionManagementController::class, 'show'])->name('revisions.show');
            Route::post('/revisions/{revision}/approve', [ProductRevisionManagementController::class, 'approve'])->name('revisions.approve');
            Route::post('/revisions/{revision}/reject', [ProductRevisionManagementController::class, 'reject'])->name('revisions.reject');
            Route::get('/', [ProductManagementController::class, 'index'])->name('index');
            Route::post('/', [ProductManagementController::class, 'store'])->name('store');
            Route::get('/{product}', [ProductManagementController::class, 'show'])->name('show');
            Route::patch('/{product}', [ProductManagementController::class, 'update'])->name('update');
            Route::delete('/{product}', [ProductManagementController::class, 'destroy'])->name('destroy');
        });

        Route::patch('/product-comments/{comment}/hide', [ProductCommentController::class, 'hide'])->name('admin.product-comments.hide');
        Route::patch('/store-comments/{comment}/hide', [StoreCommentController::class, 'hide'])->name('admin.store-comments.hide');

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

        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/statistics', [UserManagementController::class, 'statistics'])->name('statistics');
            Route::get('/{user}', [UserManagementController::class, 'show'])->name('show');
            Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
            Route::patch('/{user}/status', [UserManagementController::class, 'updateStatus'])->name('status');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
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
