<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    protected $subscriptionService;
    protected $dashboardService;

    public function __construct(SubscriptionService $subscriptionService, \App\Services\DashboardService $dashboardService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->dashboardService = $dashboardService;
    }

    public function status(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription;

        return response()->json([
            'success' => true,
            'message' => 'Subscription status retrieved successfully.',
            'data' => [
                'subscription' => $this->dashboardService->formatSubscription($user, $subscription),
            ]
        ]);
    }

    public function selectPlan(Request $request)
    {
        $request->validate([
            'plan_slug' => 'required|exists:plans,slug',
        ]);

        $plan = Plan::where('slug', $request->plan_slug)->firstOrFail();
        $user = $request->user();

        if ($plan->price == 0) {
             try {
                 $this->subscriptionService->downgradeToFree($user);
                 
                 return response()->json([
                    'success' => true,
                    'message' => 'Plan changed to Free successfully.',
                    'data' => []
                 ]);
             } catch (\Throwable $e) {
                 \Illuminate\Support\Facades\Log::error('Downgrade to Free Error: ' . $e->getMessage());
                 return response()->json([
                    'success' => false,
                    'message' => 'Failed to change plan: ' . $e->getMessage(),
                    'data' => []
                ], 400);
             }
        }
        
        try {
            $subscription = $this->subscriptionService->createTrialSubscription($user, $plan);
            
            return response()->json([
                'success' => true,
                'message' => 'Plan selected successfully. Trial started.',
                'data' => [
                    'subscription' => $this->dashboardService->formatSubscription($user, $subscription),
                ]
            ]);
        } catch (\Throwable $e) {
             \Illuminate\Support\Facades\Log::error('Select Plan Error: ' . $e->getMessage());
             return response()->json([
                'success' => false,
                'message' => 'Failed to select plan: ' . $e->getMessage(),
                'data' => []
            ], 400);
        }
    }

    public function cancel(Request $request)
    {
        try {
            $user = $request->user();
            $subscription = $this->subscriptionService->cancelSubscription($user);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully.',
                'data' => [
                    'subscription' => $this->dashboardService->formatSubscription($user, $subscription)
                ]
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Cancel Subscription Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: ' . $e->getMessage(),
                'data' => []
            ], 400);
        }
    }

    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_subscription_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        try {
            $subscription = $this->subscriptionService->verifyAndActivateSubscription(
                Auth::id(),
                $request->only(['razorpay_subscription_id', 'razorpay_payment_id', 'razorpay_signature'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment verified and subscription activated successfully.',
                'data' => [
                    'subscription' => $this->dashboardService->formatSubscription($request->user(), $subscription),
                ]
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Verification Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment: ' . $e->getMessage(),
                'data' => []
            ], 400);
        }
    }
    public function retryInit(Request $request)
    {
        $user = $request->user();

        // Find if there is a pending subscription that needs initialization
        $pendingSubscription = $user->subscriptions()
            ->where('status', 'pending')
            ->whereNull('razorpay_subscription_id')
            ->latest()
            ->first();

        if (! $pendingSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'No pending subscription found requiring initialization.',
                'data' => []
            ], 404);
        }

        try {
            $this->subscriptionService->initializeRazorpaySubscription($pendingSubscription);
            
            return response()->json([
                'success' => true,
                'message' => 'Subscription initialized successfully.',
                'data' => [
                    'subscription' => $this->dashboardService->formatSubscription($user, $pendingSubscription),
                ]
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Retry Init Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize subscription: ' . $e->getMessage(),
                'data' => []
            ], 400);
        }
    }
}
