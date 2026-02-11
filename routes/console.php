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
// Schedule plan expiry and notification commands
\Illuminate\Support\Facades\Schedule::command('linkpeak:handle-expiry')->everyMinute();
\Illuminate\Support\Facades\Schedule::command('linkpeak:send-expiry-warnings')->daily();
