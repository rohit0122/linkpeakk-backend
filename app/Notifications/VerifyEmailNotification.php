<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $token = $notifiable->verification_token;
        $verificationUrl = config('app.public_url').'/verify?lpkVerifyToken='.$token;

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Verify Your Email - '.config('app.name'))
            ->view('emails.auth.verification', [
                'verificationUrl' => $verificationUrl,
                'title' => 'Verify your email',
                'previewText' => 'Verify your email address to complete your registration.',
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
