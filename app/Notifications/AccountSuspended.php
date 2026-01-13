<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountSuspended extends Notification
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
        $renewUrl = config('app.public_url') . '/suspended';

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Action Required: Account Suspended - '.config('app.name'))
            ->view('emails.billing.suspension', [
                'userName' => $notifiable->name,
                'renewUrl' => $renewUrl,
                'title' => 'Action Required: Account Suspended',
                'previewText' => 'Your subscription has expired. Renew now to reactivate your bio page.',
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
