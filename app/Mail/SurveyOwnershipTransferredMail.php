<?php

namespace App\Mail;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SurveyOwnershipTransferredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Survey $survey,
        public User $previousOwner,
        public ?User $newOwner = null,
        public bool $isPending = false,
        public ?string $inviteEmail = null,
        public ?string $actionUrl = null
    ) {
        if (!$this->inviteEmail) {
            $this->inviteEmail = $this->newOwner?->email ?? $this->survey->pending_owner_email ?? '';
        }

        if (!$this->actionUrl) {
            if ($this->newOwner) {
                $this->actionUrl = route('surveys.summary', $this->survey);
            } else {
                $this->actionUrl = route('register', ['role' => 'independent', 'email' => $this->inviteEmail]);
            }
        }
    }

    public function envelope(): Envelope
    {
        $subject = $this->isPending
            ? "You've been invited to take ownership of '{$this->survey->title}' on KDAnalytiks"
            : "Survey Ownership Transferred: '{$this->survey->title}' - KDAnalytiks";

        return new Envelope(
            subject: $subject
        );
    }


    public function content(): Content
    {
        return new Content(
            view: 'emails.ownership_transferred',
            with: [
                'surveyTitle' => $this->survey->title,
                'previousOwnerName' => $this->previousOwner->name,
                'previousOwnerEmail' => $this->previousOwner->email,
                'newOwnerName' => $this->newOwner?->name ?? $this->inviteEmail,
                'inviteEmail' => $this->inviteEmail,
                'actionUrl' => $this->actionUrl,
                'isPending' => $this->isPending,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
