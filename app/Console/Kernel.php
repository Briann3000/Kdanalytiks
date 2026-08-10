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
        $schedule->command('socius:prune-attachments')->daily();
        $schedule->command('campaigns:process')->everyMinute();
        $schedule->command('campaigns:send-reminders')->daily();

        // Reset monthly org resource pool usage on the 1st of every month at midnight UTC
        $schedule->call(function () {
            \App\Models\OrgResourcePool::query()->update([
                'ai_analyses_used' => 0,
                'transcription_minutes_used' => 0,
                'socius_chat_sessions_used' => 0,
                'report_exports_used' => 0,
                'reset_at' => now(),
            ]);
        })->monthlyOn(1, '00:00');

        // Clean up expired organization invitations daily
        $schedule->call(function () {
            \App\Models\OrgInvitation::where('expires_at', '<', now())
                ->whereNull('accepted_at')
                ->delete();
        })->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
