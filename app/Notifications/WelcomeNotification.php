<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
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
        $dashboardUrl = config('app.public_url') . '/dashboard';

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Welcome to ' . config('app.name') . '!')
            ->view('emails.auth.welcome', [
                'name' => $notifiable->name ?? 'User',
                'dashboardUrl' => $dashboardUrl,
                'title' => 'Welcome aboard!',
                'previewText' => "Your account is verified. Let's build your amazing bio link page.",
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
