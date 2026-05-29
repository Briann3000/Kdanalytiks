<?php

namespace App\Jobs;

use App\Models\SurveyInviteRecipient;
use App\Mail\SurveyInviteReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInviteReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $recipient;

    /**
     * Create a new job instance.
     */
    public function __construct(SurveyInviteRecipient $recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->recipient->status !== 'sent') {
            return;
        }

        try {
            $survey = $this->recipient->campaign->survey;
            $inviteUrl = route('surveys.show', [
                'survey' => $survey->id,
                'invite_token' => $this->recipient->token,
            ]);

            Mail::to($this->recipient->email)->send(new SurveyInviteReminder($survey, $inviteUrl));

            $this->recipient->increment('reminder_count');
            $this->recipient->update([
                'last_reminder_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to send reminder to {$this->recipient->email}: " . $e->getMessage());
        }
    }
}
