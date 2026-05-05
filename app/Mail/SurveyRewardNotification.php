<?php

namespace App\Mail;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveyRewardNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $survey;
    public $rewardAmount;
    public $currency;
    public $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Survey $survey, $rewardAmount, $currency, $role = 'respondent')
    {
        $this->survey = $survey;
        $this->rewardAmount = $rewardAmount;
        $this->currency = $currency;
        $this->loginUrl = route('login.role', ['role' => $role]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reward Added: ' . $this->survey->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.survey_reward',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
