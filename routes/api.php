<?php

use App\Http\Controllers\Api\v1\ExampleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Example routes (demonstrating standardized responses)
Route::prefix('v1')->middleware(['api.rate.limit:60,1'])->group(function () {
    // Health check endpoint
    Route::get('/health', function () {
        return \App\Helpers\ApiResponse::success([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
        ], 'API is running');
    });

    Route::get('/examples', [ExampleController::class, 'index']);
    Route::get('/examples/{id}', [ExampleController::class, 'show']);
    Route::post('/examples', [ExampleController::class, 'store']);
    Route::put('/examples/{id}', [ExampleController::class, 'update']);
    Route::delete('/examples/{id}', [ExampleController::class, 'destroy']);

    // Example error responses
    Route::get('/examples/demo/unauthorized', [ExampleController::class, 'unauthorized']);
    Route::get('/examples/demo/forbidden', [ExampleController::class, 'forbidden']);
    Route::get('/examples/demo/error', [ExampleController::class, 'error']);
});

// Public Endpoints
Route::prefix('v1/public')->group(function () {
    Route::get('/pages/{slug}', [\App\Http\Controllers\Api\v1\PublicPageController::class, 'show']);
    Route::get('/pages/{slug}/image', [\App\Http\Controllers\Api\v1\BioPageController::class, 'image']);
    Route::get('/user/{id}/avatar', [\App\Http\Controllers\Api\v1\SettingsController::class, 'avatar']);
    Route::post('/leads', [\App\Http\Controllers\Api\v1\LeadController::class, 'store']);
    Route::get('/pages/{id}/qrcode', [\App\Http\Controllers\Api\v1\QRCodeController::class, 'show']);
    Route::get('/pages/{id}/qrcode/svg', [\App\Http\Controllers\Api\v1\QRCodeController::class, 'svg']);
    Route::post('/contact', [\App\Http\Controllers\Api\v1\ContactController::class, 'submit']);
    Route::post('/newsletter/subscribe', [\App\Http\Controllers\Api\v1\NewsletterController::class, 'subscribe']);

});

Route::prefix('v1/track')->group(function () {
    Route::post('/view', [\App\Http\Controllers\Api\v1\PublicPageController::class, 'trackView']);
    Route::post('/click', [\App\Http\Controllers\Api\v1\PublicPageController::class, 'trackClick']);
    Route::post('/like', [\App\Http\Controllers\Api\v1\PublicPageController::class, 'trackLike']);
});

// Auth Routes
Route::prefix('v1/auth')->middleware(['api.rate.limit:60,1'])->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\v1\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\v1\AuthController::class, 'login']);
    Route::post('/verify', [\App\Http\Controllers\Api\v1\AuthController::class, 'verify']);
    Route::post('/forgot-password', [\App\Http\Controllers\Api\v1\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\v1\AuthController::class, 'resetPassword']);
});

// Authenticated Routes
Route::prefix('v1')->middleware(['auth:sanctum', 'api.rate.limit:60,1'])->group(function () {
    Route::post('/auth/logout', [\App\Http\Controllers\Api\v1\AuthController::class, 'logout']);
    Route::post('/auth/resend-verification', [\App\Http\Controllers\Api\v1\AuthController::class, 'resend']);
    Route::get('/auth/me', function (Request $request) {
        return \App\Helpers\ApiResponse::success($request->user(), 'User retrieved successfully');
    });

    // Dashboard
    Route::get('/dashboard/init', [\App\Http\Controllers\Api\v1\DashboardController::class, 'init']);

    // Pages Management
    Route::apiResource('pages', \App\Http\Controllers\Api\v1\BioPageController::class);
    Route::get('/pages/{id}', [\App\Http\Controllers\Api\v1\BioPageController::class, 'show']); // For file uploads
    Route::post('/pages/{id}', [\App\Http\Controllers\Api\v1\BioPageController::class, 'update']); // For file uploads
    Route::get('/pages/{id}/leads', [\App\Http\Controllers\Api\v1\LeadController::class, 'index']);

    // Links Management
    Route::put('/links/bulk-reorder', [\App\Http\Controllers\Api\v1\LinkController::class, 'bulkReorder']);
    Route::apiResource('links', \App\Http\Controllers\Api\v1\LinkController::class);

    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Api\v1\AnalyticsController::class, 'lifetime']);
    Route::get('/analytics/charts', [\App\Http\Controllers\Api\v1\AnalyticsController::class, 'charts']);

    // AI Helpers
    Route::post('/ai/generate-link-title', [\App\Http\Controllers\Api\v1\AiController::class, 'generateLinkTitle']);
    Route::post('/ai/generate-seo', [\App\Http\Controllers\Api\v1\AiController::class, 'generateSeo']);

    // Settings
    Route::put('/settings/profile', [\App\Http\Controllers\Api\v1\SettingsController::class, 'updateProfile']);
    Route::put('/settings/password', [\App\Http\Controllers\Api\v1\SettingsController::class, 'updatePassword']);
    Route::delete('/settings/account', [\App\Http\Controllers\Api\v1\SettingsController::class, 'deleteAccount']);

    // Support
    Route::apiResource('tickets', \App\Http\Controllers\Api\v1\TicketController::class);

    // Subscriptions
    Route::get('/subscriptions/status', [\App\Http\Controllers\Api\v1\SubscriptionController::class, 'status']);
    Route::post('/subscriptions/select-plan', [\App\Http\Controllers\Api\v1\SubscriptionController::class, 'selectPlan']);
    Route::post('/subscriptions/verify', [\App\Http\Controllers\Api\v1\SubscriptionController::class, 'verifyPayment']);
    Route::post('/subscriptions/cancel', [\App\Http\Controllers\Api\v1\SubscriptionController::class, 'cancel']);
});

// Admin Endpoints
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'api.rate.limit:60,1'])->group(function () {
    // Basic role check can be added if 'role' column exists
    Route::get('/stats', [\App\Http\Controllers\Api\v1\AdminController::class, 'stats']);
    Route::get('/users', [\App\Http\Controllers\Api\v1\AdminController::class, 'users']);
    Route::post('/user/suspend', [\App\Http\Controllers\Api\v1\AdminController::class, 'suspend']);

    // AI Helpers (Placeholders)
    Route::post('/ai/generate-title', function () {
        return \App\Helpers\ApiResponse::success(['title' => 'AI Generated Title']);
    });
    Route::post('/ai/generate-seo', function () {
        return \App\Helpers\ApiResponse::success(['seo' => 'AI Generated SEO']);
    });

    // Support Tickets (Admin)
    Route::get('/tickets', [\App\Http\Controllers\Api\v1\TicketController::class, 'adminIndex']);
    Route::get('/tickets/{id}', [\App\Http\Controllers\Api\v1\TicketController::class, 'adminShow']);
    Route::put('/tickets/{id}', [\App\Http\Controllers\Api\v1\TicketController::class, 'adminUpdate']);
    Route::delete('/tickets/{id}', [\App\Http\Controllers\Api\v1\TicketController::class, 'adminDestroy']);

    // Plans Management (Admin)
    Route::apiResource('plans', \App\Http\Controllers\Api\v1\Admin\PlanController::class);

    // Newsletter Management (Admin)
    Route::get('/newsletter/subscribers', [\App\Http\Controllers\Api\v1\Admin\NewsletterController::class, 'index']);
    Route::delete('/newsletter/subscribers/{id}', [\App\Http\Controllers\Api\v1\Admin\NewsletterController::class, 'destroy']);
});

// Razorpay Webhook (Full path: /api/v1/payment/callback)
Route::post('/v1/payment/callback', [\App\Http\Controllers\Api\webhooks\RazorpayWebhookController::class, 'handle']);
