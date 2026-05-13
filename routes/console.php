<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('logs:prune')
    ->dailyAt((string) config('observability.retention.schedule_time', '02:15'))
    ->withoutOverlapping();
