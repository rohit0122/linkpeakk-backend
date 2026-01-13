<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendRenewalNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:send-renewal-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for subscriptions renewing in 2 days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = now()->addDays(2)->format('Y-m-d');
        
        $subscriptions = \App\Models\Subscription::with(['user', 'plan'])
            ->where('status', 'active')
            ->whereDate('current_period_end', $targetDate)
            ->whereHas('plan', function($query) {
                $query->whereIn('slug', ['pro', 'agency']);
            })
            ->get();

        $count = $subscriptions->count();
        $this->info("Found {$count} subscriptions renewing on {$targetDate}.");

        foreach ($subscriptions as $subscription) {
            $subscription->user->notify(new \App\Notifications\SubscriptionRenewalUpcoming($subscription));
            $this->info("Sent renewal notification to: {$subscription->user->email}");
        }

        return 0;
    }
}
