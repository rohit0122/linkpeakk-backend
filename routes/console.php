<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule subscription-related commands
\Illuminate\Support\Facades\Schedule::command('subscriptions:send-renewal-notifications')->daily();
\Illuminate\Support\Facades\Schedule::command('subscriptions:check-trial-expiry')->daily();
\Illuminate\Support\Facades\Schedule::command('subscriptions:send-trial-warnings')->daily();
