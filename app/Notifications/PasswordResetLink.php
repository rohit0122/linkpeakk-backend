<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PasswordResetLink extends Notification
{
    use Queueable;

    protected $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
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
        $resetUrl = config('app.public_url').'/reset-password?lpkVerifyToken='.$this->token;

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Reset Password - '.config('app.name'))
            ->view('emails.auth.reset-password', [
                'resetUrl' => $resetUrl,
                'title' => 'Reset your password',
                'previewText' => 'Follow the link below to reset your account password.',
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
