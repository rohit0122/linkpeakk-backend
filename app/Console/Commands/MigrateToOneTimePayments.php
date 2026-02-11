<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToOneTimePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'linkpeak:migrate-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing active subscribers to the new one-time payment schema (users table).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of active subscriptions...');

        $subscriptions = Subscription::with('user', 'plan')->whereIn('status', ['active', 'trialing', 'authenticated', 'pending'])->get();

        $this->info("Found {$subscriptions->count()} active/trialing subscriptions.");

        $bar = $this->output->createProgressBar($subscriptions->count());
        $bar->start();

        foreach ($subscriptions as $sub) {
            $user = $sub->user;
            if (!$user) continue;

            $user->update([
                'plan_id' => $sub->plan_id,
                'plan_expires_at' => $sub->current_period_end ?: $sub->trial_ends_at,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Migration completed successfully.');
    }
}
