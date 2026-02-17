<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PlanExpiryNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendPlanExpiryNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'linkpeak:send-expiry-warnings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send plan and trial expiry warning notifications (7, 3, 1 days before).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting plan expiry notification process...');

        $warningDays = [7, 3, 1];

        foreach ($warningDays as $days) {
            $targetDate = Carbon::now()->addDays($days)->format('Y-m-d');
            
            $users = User::whereNotNull('plan_expires_at')
                ->whereDate('plan_expires_at', $targetDate)
                ->whereHas('plan', function($query) {
                    $query->where('slug', '!=', 'free');
                })
                ->get();

            $this->info("Found {$users->count()} users with plan/trial expiring in {$days} days.");

            foreach ($users as $user) {
                // Determine if it's a trial or a paid plan
                // A trial is a user nested in a plan with trial_days > 0 but no captured payments yet
                $hasPayments = $user->payments()->where('status', 'captured')->where('plan_id', $user->plan_id)->exists();
                
                if (!$hasPayments && $user->plan && $user->plan->trial_days > 0) {
                    $user->notify(new \App\Notifications\TrialExpiringNotification($user, $days));
                } else {
                    $user->notify(new PlanExpiryNotification($user, 'warning', $days));
                }
                
                $this->info("Notification sent to: {$user->email}");
            }
        }

        $this->info('Plan expiry notifications completed.');
    }
}
