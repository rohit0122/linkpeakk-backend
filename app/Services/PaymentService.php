<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    /**
     * Create a Razorpay payment link for a one-time payment.
     */
    public function createPaymentLink(User $user, Plan $plan)
    {
        // Business Rule: User can upgrade or downgrade only in the last 7 days before expiry.
        if (!$user->isInRenewalWindow()) {
            if ($user->plan_id != $plan->id) {
                throw new Exception("You can only change your plan in the last 7 days before your current plan expires.");
            }
        }

        try {
            $description = "Payment for {$plan->name} plan - 30 Days Access";
            $metadata = [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'plan_slug' => $plan->slug
            ];

            $paymentLink = $this->razorpayService->createPaymentLink(
                $plan->price, 
                $plan->currency ?? 'INR', 
                $description, 
                $metadata,
                $user
            );

            // Track payment intent
            Payment::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'razorpay_order_id' => $paymentLink->order_id, // Links have parent order_id
                'amount' => $plan->price,
                'currency' => $plan->currency ?? 'INR',
                'status' => 'created',
            ]);

            return $paymentLink->short_url;
        } catch (Exception $e) {
            Log::error('Failed to create payment link: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle successful payment from webhook or client verify.
     */
    public function handlePaymentSuccess($razorpayOrderId, $razorpayPaymentId)
    {
        return DB::transaction(function () use ($razorpayOrderId, $razorpayPaymentId) {
            $payment = Payment::where('razorpay_order_id', $razorpayOrderId)->first();

            if (!$payment) {
                Log::error("Payment not found for Order ID: {$razorpayOrderId}");
                return null;
            }

            if ($payment->status === 'captured') {
                return $payment; // Already processed
            }

            $user = $payment->user;
            $newPlan = $payment->plan;
            $currentPlan = $user->plan;

            $now = Carbon::now();
            $newExpiry = null;

            // Logic for Expiry and Plan Change
            if (!$user->plan_id || $user->plan_id == $newPlan->id) {
                // Case 1: First payment or Same Plan (Renewal)
                // expiry = max(now, current_expiry) + 30 days
                $baseDate = ($user->plan_expires_at && $user->plan_expires_at->isFuture()) 
                            ? $user->plan_expires_at 
                            : $now;
                
                $newExpiry = $baseDate->copy()->addDays(30);
                
                $user->update([
                    'plan_id' => $newPlan->id,
                    'plan_expires_at' => $newExpiry,
                    'pending_plan_id' => null
                ]);
            } else {
                // Case 2: Plan Change (Upgrade or Downgrade)
                // Determine if it's an Upgrade (Higher Price) OR Downgrade (Lower Price)
                $isUpgrade = $newPlan->price > ($currentPlan ? $currentPlan->price : 0);

                if ($isUpgrade) {
                    // Upgrades apply immediately
                    $baseDate = ($user->plan_expires_at && $user->plan_expires_at->isFuture()) 
                                ? $user->plan_expires_at 
                                : $now;
                    
                    $newExpiry = $baseDate->copy()->addDays(30);
                    $user->update([
                        'plan_id' => $newPlan->id,
                        'plan_expires_at' => $newExpiry,
                        'pending_plan_id' => null
                    ]);
                } else {
                    // Downgrades only take effect after current plan expires
                    // So we set pending_plan_id
                    $user->update([
                        'pending_plan_id' => $newPlan->id
                    ]);
                    // Expiry remains same
                    $newExpiry = $user->plan_expires_at;
                }
            }

            $payment->update([
                'razorpay_payment_id' => $razorpayPaymentId,
                'status' => 'captured',
                'expires_at_after_payment' => $newExpiry
            ]);

            return $payment;
        });
    }

    /**
     * Cron Job Logic: Check and handle expired plans.
     */
    public function handleExpiries()
    {
        $expiredUsers = User::whereNotNull('plan_id')
            ->where('plan_expires_at', '<', Carbon::now())
            ->get();

        foreach ($expiredUsers as $user) {
            DB::transaction(function () use ($user) {
                if ($user->pending_plan_id) {
                    // Apply pending plan (Downgrade or Delayed Renewal)
                    $user->update([
                        'plan_id' => $user->pending_plan_id,
                        'plan_expires_at' => Carbon::now()->addDays(30),
                        'pending_plan_id' => null
                    ]);
                    
                    // Notify user about transition
                    $user->notify(new \App\Notifications\PlanExpiryNotification($user, 'expired'));
                    
                    Log::info("User {$user->id} moved to pending plan {$user->plan_id}");
                } else {
                    // Fallback to Free
                    $freePlan = Plan::where('slug', 'free')->first() ?? Plan::where('price', 0)->first();
                    $user->update([
                        'plan_id' => $freePlan ? $freePlan->id : null,
                        'plan_expires_at' => null,
                        'pending_plan_id' => null
                    ]);

                    // Notify user about transition to free
                    $user->notify(new \App\Notifications\PlanExpiryNotification($user, 'expired'));

                    Log::info("User {$user->id} expired and moved to Free plan.");
                }
            });
        }
    }

    /**
     * Update payment status based on webhook events (e.g. expired, cancelled).
     */
    public function updatePaymentStatus($razorpayOrderId, $status)
    {
        $payment = Payment::where('razorpay_order_id', $razorpayOrderId)->first();

        if ($payment) {
            $payment->update(['status' => $status]);
            Log::info("Updated payment status for Order ID {$razorpayOrderId} to {$status}");
        } else {
            Log::warning("Payment not found for Order ID {$razorpayOrderId} during status update to {$status}");
        }
    }
}
