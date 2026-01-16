<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletedAdminEmail extends Mailable
{
    use SerializesModels;

    public $name;

    public $email;

    public $deletedAt;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $email)
    {
        $this->name = $name;
        $this->email = $email;
        $this->deletedAt = now()->toDateTimeString();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Account Deleted: '.$this->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.account-deleted-admin',
            with: [
                'name' => $this->name,
                'email' => $this->email,
                'deletedAt' => $this->deletedAt,
                'title' => 'User Account Deleted',
                'previewText' => 'A user has permanently deleted their account: '.$this->email,
            ],
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
