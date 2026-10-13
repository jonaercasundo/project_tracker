<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('bidding:cleanup-files --limit=100')->everyFiveMinutes()->withoutOverlapping(10);
Schedule::command('logistics:webhooks:recover --limit=100')->everyMinute()->withoutOverlapping(1);
