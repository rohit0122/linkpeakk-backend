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

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function status(Request $request)
    {
        $user = $request->user();
        $subscription = $user->activeSubscription;

        return response()->json([
            'success' => true,
            'message' => 'Subscription status retrieved successfully.',
            'data' => [
                'plan' => $subscription ? $subscription->plan : null,
                'subscription' => $subscription,
                'is_active' => $subscription ? $subscription->isActive() : false,
                'trial_ends_at' => $subscription ? $subscription->trial_ends_at : null,
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
             // Handle checking to free plan logic if needed, usually just cancelling paid sub
             return response()->json([
                'success' => true,
                'message' => 'Free plan selected.',
                'data' => []
             ]);
        }
        
        try {
            $subscription = $this->subscriptionService->createTrialSubscription($user, $plan);
            
            return response()->json([
                'success' => true,
                'message' => 'Plan selected successfully. Trial started.',
                'data' => [
                    'razorpay_subscription_id' => $subscription->razorpay_subscription_id,
                    'plan' => [
                        'name' => $plan->name,
                        'price' => $plan->price,
                    ],
                    'prefill' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'subscription' => $subscription,
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
                    'subscription' => $subscription
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
}
