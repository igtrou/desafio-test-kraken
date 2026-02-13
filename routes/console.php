<?php

use Illuminate\Support\Facades\Schedule;

$autoCollectEnabled = (bool) config('quotations.auto_collect.enabled', false);
$intervalMinutes = max(1, min(59, (int) config('quotations.auto_collect.interval_minutes', 15)));

if ($autoCollectEnabled) {
    // Interval is bounded to avoid invalid cron expressions and noisy schedules.
    Schedule::command('quotations:collect', [
        '--trigger' => 'scheduler',
    ])
        ->cron("*/{$intervalMinutes} * * * *")
        ->withoutOverlapping();
}
