<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringNotification extends Notification
{
    use Queueable;

    protected $subscription;
    protected $daysLeft;

    /**
     * Create a new notification instance.
     */
    public function __construct($subscription, $daysLeft = 3)
    {
        $this->subscription = $subscription;
        $this->daysLeft = $daysLeft;
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
    public function toMail(object $notifiable): MailMessage
    {
        $renewUrl = config('app.public_url').'/dashboard';

        return (new MailMessage)
            ->subject('Your Trial Expires Soon - '.config('app.name'))
            ->view('emails.billing.reminder', [
                'userName' => $notifiable->name ?? 'User',
                'trial' => true,
                'daysLeft' => $this->daysLeft,
                'isUrgent' => $this->daysLeft <= 3,
                'renewUrl' => $renewUrl,
                'title' => 'Your Trial is about to expire',
                'previewText' => 'To keep your bio page live, please renew your plan.',
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
