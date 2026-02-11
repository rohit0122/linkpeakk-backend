<?php

namespace App\Console\Commands;

use App\Services\PaymentService;
use Illuminate\Console\Command;

class HandlePlanExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'linkpeak:handle-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle user plan expiry and switch to pending plans or free plan.';

    /**
     * Execute the console command.
     */
    public function handle(PaymentService $paymentService)
    {
        $this->info('Checking for expired plans...');
        $paymentService->handleExpiries();
        $this->info('Plan expiry handling completed.');
    }
}
