<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $inviteUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Undangan Akun Admin - ATL Express',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user-invitation',
            with: [
                'user'      => $this->user,
                'inviteUrl' => $this->inviteUrl,
                'expiresAt' => $this->user->invite_expires_at,
            ],
        );
    }
}

