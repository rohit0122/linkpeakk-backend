<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiredNotification extends Notification
{
    use Queueable;

    protected $subscription;

    /**
     * Create a new notification instance.
     */
    public function __construct($subscription)
    {
        $this->subscription = $subscription;
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
        $upgradeUrl = config('app.public_url') . '/dashboard';
        $supportUrl = config('app.public_url') . '/support';
        $planName = $this->subscription->plan->name ?? 'Premium';

        return (new MailMessage)
            ->subject('Your Trial Has Expired - Action Required')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("Your **{$planName}** trial has expired and your account has been suspended.")
            ->line('To reactivate your account and continue using premium features, please upgrade to a paid subscription.')
            ->action('Upgrade Now', $upgradeUrl)
            ->line('If you need assistance or have questions, please contact our support team.')
            ->action('Contact Support', $supportUrl)
            ->line('We hope to see you back soon!');
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
