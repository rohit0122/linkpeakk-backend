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
    protected $description = 'Send plan expiry warning notifications (7, 5, 2 days before).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting plan expiry notification process...');

        $warningDays = [7, 5, 2];

        foreach ($warningDays as $days) {
            $targetDate = Carbon::now()->addDays($days)->format('Y-m-d');
            
            $users = User::whereNotNull('plan_expires_at')
                ->whereDate('plan_expires_at', $targetDate)
                ->whereHas('plan', function($query) {
                    $query->where('slug', '!=', 'free');
                })
                ->get();

            $this->info("Found {$users->count()} users with plan expiring in {$days} days.");

            foreach ($users as $user) {
                $user->notify(new PlanExpiryNotification($user, 'warning', $days));
                $this->info("Notification sent to: {$user->email}");
            }
        }

        $this->info('Plan expiry notifications completed.');
    }
}
