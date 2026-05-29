<?php

namespace App\Console\Commands;

use App\Models\SurveyInviteCampaign;
use App\Models\SurveyInviteRecipient;
use App\Jobs\SendInviteReminderJob;
use Illuminate\Console\Command;

class SendAutoReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automatic email reminders to non-respondents in active campaigns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaigns = SurveyInviteCampaign::where('auto_reminders', true)
            ->whereIn('status', ['sending', 'completed'])
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns with auto reminders enabled.');
            return 0;
        }

        $reminderCount = 0;

        foreach ($campaigns as $campaign) {
            $days = $campaign->reminder_interval_days;

            $recipients = $campaign->recipients()
                ->where('status', 'sent')
                ->where(function ($query) use ($days) {
                    $query->where('sent_at', '<=', now()->subDays($days))
                        ->where(function ($q) use ($days) {
                            $q->whereNull('last_reminder_at')
                                ->orWhere('last_reminder_at', '<=', now()->subDays($days));
                        });
                })
                ->get();

            foreach ($recipients as $recipient) {
                SendInviteReminderJob::dispatch($recipient);
                $reminderCount++;
            }
        }

        $this->info("Dispatched {$reminderCount} reminder jobs.");
        return 0;
    }
}
