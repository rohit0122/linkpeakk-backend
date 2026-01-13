<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class SubscriptionService
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    public function initializeRazorpaySubscription(Subscription $subscription)
    {
        $user = $subscription->user;
        $plan = $subscription->plan;

        $trialEndsAt = Carbon::now()->addDays($plan->trial_days); // 7 days default
        
        // Check if user already has a customer ID in any of their subscriptions
        $customerId = $user->subscriptions()->whereNotNull('razorpay_customer_id')->value('razorpay_customer_id');

        if (!$customerId) {
            $customerId = $this->razorpayService->getOrCreateCustomer($user->name, $user->email);
        }

        $razorpaySub = $this->razorpayService->createSubscription(
            $plan->razorpay_plan_id,
            $customerId,
            120, // 10 years roughly
            $trialEndsAt->timestamp
        );

        $subscription->update([
            'razorpay_subscription_id' => $razorpaySub->id,
            'razorpay_customer_id' => $customerId,
            'status' => 'trialing',
            'trial_ends_at' => $trialEndsAt,
            'current_period_start' => Carbon::now(),
            'current_period_end' => $trialEndsAt,
        ]);

        return $subscription;
    }

    public function createTrialSubscription(User $user, Plan $plan)
    {
        \Illuminate\Support\Facades\Log::info("Starting subscription upgrade/creation for user {$user->id} to plan {$plan->id}");
        
        // Check if user already has an active PAID subscription
        $activeSub = $user->activeSubscription;
        if ($activeSub && $activeSub->plan && $activeSub->plan->price > 0) {
            throw new Exception("User already has an active paid subscription (ID: {$activeSub->id}). Cannot upgrade to another paid plan without cancellation.");
        }

        // Create Razorpay Customer if not exists
        // (Assuming we store razorpay_customer_id in users table or retrieve it. 
        // For this MVP, let's assume we might create it on the fly or just use email)
        // Ideally User model should have razorpay_customer_id. 
        // Let's create one if needed, but for 'createSubscription' API we usually need it.
        
        // For manual upgrades (FEE => PRO/AGENCY, etc.), we do NOT offer a trial.
        // It begins immediately.
        $trialEndsAt = Carbon::now();
        
        // Create actual Razorpay subscription starting in future
        // We first need a customer in Razorpay
        
        // TODO: Ensure user has razorpay_customer_id in database if we want to reuse it.
        // For now, let's assume we create/fetch it.
        
        // This is a simplified logic. In prod, update User model to store `razorpay_customer_id`.
        // Check if user already has a customer ID in any of their subscriptions
        $customerId = $user->subscriptions()->whereNotNull('razorpay_customer_id')->value('razorpay_customer_id');

        if (!$customerId) {
            $customerId = $this->razorpayService->getOrCreateCustomer($user->name, $user->email);
        }

        $razorpaySub = $this->razorpayService->createSubscription(
            $plan->razorpay_plan_id,
            $customerId,
            120, // 10 years roughly
            null // Use null for immediate start (avoids clock-sync errors)
        );

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'razorpay_subscription_id' => $razorpaySub->id,
            'razorpay_customer_id' => $customerId, // Save this if we updated migration
            'status' => 'trialing',
            'trial_ends_at' => $trialEndsAt,
            'current_period_start' => Carbon::now(),
            'current_period_end' => $trialEndsAt,
        ]);
    }

    public function activateSubscription($razorpaySubscriptionId)
    {
        $subscription = Subscription::where('razorpay_subscription_id', $razorpaySubscriptionId)->first();
        
        if (!$subscription) {
            return;
        }

        // Fetch latest status from Razorpay to be sure
        try {
            $rzpSub = $this->razorpayService->fetchSubscription($razorpaySubscriptionId);
            
            $subscription->update([
                'status' => $rzpSub->status,
                'current_period_start' => Carbon::createFromTimestamp($rzpSub->current_start),
                'current_period_end' => Carbon::createFromTimestamp($rzpSub->current_end),
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to sync activated subscription: ' . $e->getMessage());
        }
    }

    public function cancelSubscription(User $user)
    {
        $subscription = $user->activeSubscription;

        if (!$subscription) {
            throw new Exception("No active subscription found.");
        }

        $this->razorpayService->cancelSubscription($subscription->razorpay_subscription_id, true); // Cancel at cycle end

        $subscription->update([
            'cancelled_at' => Carbon::now(),
        ]);
        
        return $subscription;
    }

    /**
     * Check if user's trial has expired and suspend if no active subscription exists.
     */
    public function checkAndHandleTrialExpiry(User $user)
    {
        // 1. If already suspended, no need to check
        if (!$user->is_active || $user->suspended_at) {
            return;
        }

        // 2. Check for any 'active' paid subscription
        $hasActivePaid = $user->subscriptions()
            ->where('status', 'active')
            ->whereHas('plan', function($query) {
                $query->where('price', '>', 0);
            })
            ->exists();

        if ($hasActivePaid) {
            return;
        }

        // 3. Check for expired 'trialing' subscriptions
        $hasExpiredTrial = $user->subscriptions()
            ->where('status', 'trialing')
            ->where('trial_ends_at', '<', now())
            ->exists();

        if ($hasExpiredTrial) {
            $user->update([
                'is_active' => false,
                'suspended_at' => now(),
                'suspension_reason' => 'Your trial period has expired. Please contact support to reactivate your account and upgrade to a paid plan.',
            ]);

            \Illuminate\Support\Facades\Log::info("User ID {$user->id} suspended due to trial expiry.");
        }
    }
}
