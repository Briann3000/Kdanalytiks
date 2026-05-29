<?php

namespace App\Jobs;

use App\Models\SurveyInviteCampaign;
use App\Models\SurveyInviteRecipient;
use App\Mail\SurveyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInviteCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;

    /**
     * Create a new job instance.
     */
    public function __construct(SurveyInviteCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->campaign->refresh();

        if (in_array($this->campaign->status, ['completed', 'cancelled'])) {
            return;
        }

        $this->campaign->update(['status' => 'sending']);

        $recipients = $this->campaign->recipients()
            ->where('status', 'pending')
            ->get();

        foreach ($recipients as $recipient) {
            $this->campaign->refresh();
            if ($this->campaign->status === 'cancelled') {
                break;
            }

            try {
                $inviteUrl = route('surveys.show', [
                    'survey' => $this->campaign->survey_id,
                    'invite_token' => $recipient->token,
                ]);

                Mail::to($recipient->email)->send(new SurveyInvitation($this->campaign->survey, $inviteUrl));

                $recipient->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                $this->campaign->increment('total_sent');
            } catch (\Exception $e) {
                \Log::error("Failed to send invite to {$recipient->email}: " . $e->getMessage());
                $recipient->update([
                    'status' => 'bounced',
                ]);
            }
        }

        $this->campaign->refresh();
        if ($this->campaign->status !== 'cancelled') {
            $hasMorePending = $this->campaign->recipients()->where('status', 'pending')->exists();
            if (!$hasMorePending) {
                $this->campaign->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }
        }
    }
}
