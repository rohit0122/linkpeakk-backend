<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

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

        // Default to a 7-day trial if not specified in the plan
        $trialDays = $plan->trial_days ?? 7;
        $trialEndsAt = Carbon::now()->addDays($trialDays);

        // Check if user already has a customer ID
        $customerId = $user->subscriptions()->whereNotNull('razorpay_customer_id')->value('razorpay_customer_id');

        if (! $customerId) {
            $customerId = $this->razorpayService->getOrCreateCustomer($user->name, $user->email);
        }

        // Razorpay only allows start_at in the future.
        // If trialDays is greater than 0, we set start_at.
        $startAt = $trialDays > 0 ? $trialEndsAt->timestamp : null;

        \Illuminate\Support\Facades\Log::info("Initializing Razorpay subscription for user {$user->id} with plan {$plan->slug}. Trial days: {$trialDays}");

        try {
            $razorpaySub = $this->razorpayService->createSubscription(
                $plan->razorpay_plan_id,
                $customerId,
                120, // 10 years
                $startAt
            );

            $subscription->update([
                'razorpay_subscription_id' => $razorpaySub->id,
                'razorpay_customer_id' => $customerId,
                'status' => $trialDays > 0 ? 'trialing' : 'active',
                'trial_ends_at' => $trialDays > 0 ? $trialEndsAt : null,
                'current_period_start' => Carbon::now(),
                'current_period_end' => $trialEndsAt,
            ]);

            return $subscription;
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to create Razorpay subscription for user {$user->id}: ".$e->getMessage());
            throw $e;
        }
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

        if (! $customerId) {
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

        if (! $subscription) {
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
            Log::error('Failed to sync activated subscription: '.$e->getMessage());
        }
    }

    public function cancelSubscription(User $user)
    {
        $subscription = $user->activeSubscription;

        if (! $subscription) {
            throw new Exception('No active subscription found.');
        }

        $this->razorpayService->cancelSubscription($subscription->razorpay_subscription_id, true); // Cancel at cycle end

        $subscription->update([
            'cancelled_at' => Carbon::now(),
        ]);

        return $subscription;
    }

    /**
     * Downgrade user to free plan (cancel active paid subscription).
     */
    public function downgradeToFree(User $user)
    {
        $activeSub = $user->activeSubscription;

        if ($activeSub) {
            // Cancel the active subscription in Razorpay
            if ($activeSub->razorpay_subscription_id) {
                try {
                    // Cancel immediately or at cycle end?
                    // Usually for downgrade to free, we might want to let them finish their term
                    // OR cancel immediately. Let's cancel immediately for now as per requirement implication.
                    // If we want them to finish the term, we just set cancel_at_period_end = true
                    // and let the webhook handle the actual status change.
                    // BUT, if the UI says "Select Free" and we return "Success", the user expects it to be done.
                    $this->razorpayService->cancelSubscription($activeSub->razorpay_subscription_id, false);
                } catch (\Exception $e) {
                    // Log error but proceed to update local DB to reflect intent?
                    // Or fail? Best to log and proceed if possible, or rethrow.
                    \Illuminate\Support\Facades\Log::error("Failed to cancel Razorpay subscription {$activeSub->razorpay_subscription_id}: ".$e->getMessage());
                    // We might still want to mark it locally if we trust the intent.
                    // But let's throw for now to be safe, or handle gracefully.
                }
            }

            $activeSub->update([
                'cancelled_at' => Carbon::now(),
                'status' => 'cancelled', // Or keep 'active' until period ends if using cancel_at_period_end
                'ends_at' => Carbon::now(),
            ]);
        }

        // Technically "Free" might not need a subscription record,
        // OR we create a specific record for the Free plan.
        // Based on PlanSeeder, Free is a plan.
        $freePlan = Plan::where('slug', 'free')->first();

        if ($freePlan) {
            // Create a local subscription record for Free just for tracking?
            // Or usually, no active subscription means free?
            // Let's create one for consistency if the system relies on $user->activeSubscription to check limits.
            // But usually systems rely on "No Paid Sub = Free".
            // However, checks in User.php:
            // $subscription = $this->activeSubscription()->with('plan')->first();
            // if (!$subscription) ... default to Free.
            // So we actually DON'T need a row for Free plan necessarily.
            // But if we want to be explicit:

            // return Subscription::create([...]);
        }

        return $activeSub;
    }

    /**
     * Check if user's trial has expired and suspend if no active subscription exists.
     */
    public function checkAndHandleTrialExpiry(User $user)
    {
        // 1. If already suspended, no need to check
        if (! $user->is_active || $user->suspended_at) {
            return;
        }

        // 2. Check for any 'active' equivalent paid subscription
        $hasActivePaid = $user->subscriptions()
            ->whereIn('status', ['active', 'trialing', 'authenticated', 'pending', 'created', 'created'])
            ->whereHas('plan', function ($query) {
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
            // Get the expired subscription for email context
            $expiredSubscription = $user->subscriptions()
                ->where('status', 'trialing')
                ->where('trial_ends_at', '<', now())
                ->with('plan')
                ->first();

            $user->update([
                'is_active' => false,
                'suspended_at' => now(),
                'suspension_reason' => 'Your trial period has expired. Please contact support to reactivate your account and upgrade to a paid plan.',
            ]);

            // Send notification email
            if ($expiredSubscription) {
                $user->notify(new \App\Notifications\TrialExpiredNotification($expiredSubscription));
            }

            \Illuminate\Support\Facades\Log::info("User ID {$user->id} suspended due to trial expiry.");
        }
    }

    /**
     * Verify payment signature and activate subscription.
     */
    public function verifyAndActivateSubscription($userId, array $data)
    {
        $razorpaySubscriptionId = $data['razorpay_subscription_id'];
        $razorpayPaymentId = $data['razorpay_payment_id'];
        $razorpaySignature = $data['razorpay_signature'];

        // 1. Verify Signature
        $attributes = [
            'razorpay_subscription_id' => $razorpaySubscriptionId,
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature' => $razorpaySignature,
        ];

        if (! $this->razorpayService->verifyPaymentSignature($attributes)) {
            throw new Exception('Invalid payment signature.');
        }

        // 2. Fetch/Update local subscription
        $subscription = Subscription::where('user_id', $userId)
            ->where('razorpay_subscription_id', $razorpaySubscriptionId)
            ->first();

        if (! $subscription) {
            throw new Exception('Subscription not found for this user.');
        }

        // 3. Sync with Razorpay to get accurate state/dates
        try {
            $rzpSub = $this->razorpayService->fetchSubscription($razorpaySubscriptionId);

            $subscription->update([
                'status' => $rzpSub->status,
                'current_period_start' => ($rzpSub->current_start) ? Carbon::createFromTimestamp($rzpSub->current_start) : $subscription->current_period_start,
                'current_period_end' => ($rzpSub->current_end) ? Carbon::createFromTimestamp($rzpSub->current_end) : $subscription->current_period_end,
            ]);

            // Sync trial end date if Razorpay provides it
            $trialEnd = isset($rzpSub->trial_end) ? Carbon::createFromTimestamp($rzpSub->trial_end) : null;
            $subscription->update(['trial_ends_at' => $trialEnd]);

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Razorpay Sync Failed during verification: '.$e->getMessage());
            // We still have valid signature, so we can mark it as active if sync fails
            $subscription->update(['status' => 'active']);
        }

        return $subscription->load('plan');
    }

    /**
     * Synchronize all local subscriptions with Razorpay.
     */
    public function syncAllSubscriptions()
    {
        $subscriptions = Subscription::whereNotNull('razorpay_subscription_id')->get();
        $results = [
            'total' => $subscriptions->count(),
            'synced' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($subscriptions as $subscription) {
            try {
                $this->syncSubscriptionWithRazorpay($subscription);
                $results['synced']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Sub ID {$subscription->razorpay_subscription_id}: " . $e->getMessage();
                \Illuminate\Support\Facades\Log::error("Failed to sync subscription {$subscription->id}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Sync a single local subscription with Razorpay.
     */
    public function syncSubscriptionWithRazorpay(Subscription $subscription)
    {
        if (!$subscription->razorpay_subscription_id) {
            return;
        }

        try {
            $rzpSub = $this->razorpayService->fetchSubscription($subscription->razorpay_subscription_id);

            $subscription->update([
                'status' => $rzpSub->status,
                'current_period_start' => ($rzpSub->current_start) ? \Carbon\Carbon::createFromTimestamp($rzpSub->current_start) : $subscription->current_period_start,
                'current_period_end' => ($rzpSub->current_end) ? \Carbon\Carbon::createFromTimestamp($rzpSub->current_end) : $subscription->current_period_end,
                'trial_ends_at' => (isset($rzpSub->trial_end) && $rzpSub->trial_end) ? \Carbon\Carbon::createFromTimestamp($rzpSub->trial_end) : $subscription->trial_ends_at,
            ]);

            // Handle cancelled status locally if needed
            if ($rzpSub->status === 'cancelled' && !$subscription->cancelled_at) {
                $subscription->update(['cancelled_at' => \Carbon\Carbon::now()]);
            }

            return $subscription;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Razorpay Sync Failed for subscription {$subscription->razorpay_subscription_id}: " . $e->getMessage());
            throw $e;
        }
    }
}
