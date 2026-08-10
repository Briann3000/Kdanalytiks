<?php

namespace App\Mail;

use App\Models\OrgInvitation;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrgInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrgInvitation $invitation,
        public Organization $org
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invitation to join {$this->org->name} on KDAnalytiks"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.org_invitation',
            with: [
                'acceptUrl' => route('org.invite.show', $this->invitation->token),
                'orgName' => $this->org->name,
                'role' => str_replace('_', ' ', ucfirst($this->invitation->org_workspace_role)),
                'inviterName' => $this->invitation->invitedBy->name,
                'expiresAt' => $this->invitation->expires_at ? $this->invitation->expires_at->format('M j, Y') : 'N/A',
                'message' => $this->invitation->message,
                'logoUrl' => $this->org->logo_url,
            ]
        );
    }
}
