<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReceiptEmail extends Mailable
{
    use SerializesModels;

    public $name;
    public $subject;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $subject)
    {
        $this->name = $name;
        $this->subject = $subject;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "We've received your message",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.support.contact-receipt',
            with: [
                'name' => $this->name,
                'subject' => $this->subject,
                'title' => "We've received your message",
                'previewText' => 'Thanks for contacting ' . config('app.name'),
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
