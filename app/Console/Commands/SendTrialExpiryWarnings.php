<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\TrialExpiringNotification;
use Illuminate\Console\Command;

class SendTrialExpiryWarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:send-trial-warnings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send warning emails to users whose trials expire in 3 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for trials expiring in 3 days...');

        $targetDate = now()->addDays(3);

        // Find subscriptions expiring in 3 days
        $subscriptions = Subscription::with(['user', 'plan'])
            ->where('status', 'trialing')
            ->whereDate('trial_ends_at', $targetDate->format('Y-m-d'))
            ->whereHas('plan', function ($query) {
                $query->where('price', '>', 0);
            })
            ->get();

        $count = $subscriptions->count();
        $this->info("Found {$count} trials expiring on {$targetDate->format('Y-m-d')}.");

        foreach ($subscriptions as $subscription) {
            // Only send if user is still active
            if ($subscription->user && $subscription->user->is_active) {
                $subscription->user->notify(new TrialExpiringNotification($subscription));
                $this->info("Sent trial expiry warning to: {$subscription->user->email}");
            }
        }

        return 0;
    }
}
