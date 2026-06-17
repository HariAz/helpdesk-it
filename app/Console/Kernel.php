<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('helpdesk:check-sla --notify')->everyFifteenMinutes();
        $schedule->command('helpdesk:auto-close')->dailyAt('00:00');
        $schedule->command('helpdesk:send-summary')->dailyAt('07:00');
        $schedule->command('helpdesk:send-weekly-report')->weeklyOn(1, '07:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
