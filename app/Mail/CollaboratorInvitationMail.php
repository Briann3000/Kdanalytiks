<?php

namespace App\Mail;

use App\Models\Survey;
use App\Models\SurveyPermission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CollaboratorInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Survey $survey,
        public SurveyPermission $permission,
        public User $inviter,
        public ?string $inviteEmail = null,
        public ?string $actionUrl = null
    ) {
        if (!$this->inviteEmail) {
            $this->inviteEmail = $this->permission->user?->email ?? $this->permission->invite_email ?? '';
        }
        if (!$this->actionUrl) {
            if ($this->permission->user_id) {
                $this->actionUrl = route('surveys.summary', $this->survey);
            } else {
                $this->actionUrl = route('register', ['role' => 'independent', 'email' => $this->inviteEmail]);
            }
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to collaborate on '{$this->survey->title}' - KDAnalytiks"
        );
    }

    public function content(): Content
    {
        $permissionsList = [];
        $permissionLabels = [
            'view_form' => 'View form & layout',
            'edit_form' => 'Edit form & questions',
            'view_submissions' => 'View submissions & data',
            'add_submissions' => 'Add submissions',
            'edit_submissions' => 'Edit submissions',
            'validate_submissions' => 'Validate submissions',
            'delete_submissions' => 'Delete submissions',
            'manage_project' => 'Manage project & settings',
        ];

        if (is_array($this->permission->permissions)) {
            foreach ($this->permission->permissions as $key => $val) {
                if ($val && isset($permissionLabels[$key])) {
                    $permissionsList[] = $permissionLabels[$key];
                }
            }
        }

        if (empty($permissionsList)) {
            $permissionsList[] = 'View form & submissions';
        }

        return new Content(
            view: 'emails.collaborator_invite',
            with: [
                'surveyTitle' => $this->survey->title,
                'inviterName' => $this->inviter->name,
                'inviterEmail' => $this->inviter->email,
                'inviteEmail' => $this->inviteEmail,
                'actionUrl' => $this->actionUrl,
                'permissionsList' => $permissionsList,
                'isRegistered' => $this->permission->user_id !== null,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

