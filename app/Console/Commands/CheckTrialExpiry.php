<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TrialExpiredNotification;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class CheckTrialExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-trial-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired trials and suspend accounts';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionService $subscriptionService)
    {
        $this->info('Checking for expired trials...');

        // Get all active users
        $users = User::where('is_active', true)
            ->whereNull('suspended_at')
            ->get();

        $suspendedCount = 0;

        foreach ($users as $user) {
            // Check if user has expired trial
            $hasExpiredTrial = $user->subscriptions()
                ->where('status', 'trialing')
                ->where('trial_ends_at', '<', now())
                ->whereHas('plan', function ($query) {
                    $query->where('price', '>', 0);
                })
                ->exists();

            // Check if user has any active paid subscription
            $hasActivePaid = $user->subscriptions()
                ->whereIn('status', ['active', 'authenticated', 'pending'])
                ->whereHas('plan', function ($query) {
                    $query->where('price', '>', 0);
                })
                ->exists();

            if ($hasExpiredTrial && !$hasActivePaid) {
                // Get the expired subscription for email context
                $expiredSubscription = $user->subscriptions()
                    ->where('status', 'trialing')
                    ->where('trial_ends_at', '<', now())
                    ->with('plan')
                    ->first();

                // Suspend the user
                $user->update([
                    'is_active' => false,
                    'suspended_at' => now(),
                    'suspension_reason' => 'Your trial period has expired. Please upgrade to a paid plan to reactivate your account.',
                ]);

                // Send notification
                if ($expiredSubscription) {
                    $user->notify(new TrialExpiredNotification($expiredSubscription));
                }

                $this->info("Suspended user: {$user->email}");
                $suspendedCount++;
            }
        }

        $this->info("Total users suspended: {$suspendedCount}");

        return 0;
    }
}
