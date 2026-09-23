<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler — بخش ۳۳
|--------------------------------------------------------------------------
*/
Schedule::command('coupons:deactivate-expired')->daily();
Schedule::command('carts:send-abandoned-reminders')->hourly();
