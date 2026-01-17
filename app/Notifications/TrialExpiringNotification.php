<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringNotification extends Notification
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
        $trialEndsAt = $this->subscription->trial_ends_at->format('F j, Y');
        $upgradeUrl = config('app.public_url') . '/dashboard';
        $planName = $this->subscription->plan->name ?? 'Premium';

        return (new MailMessage)
            ->subject('Your Trial Expires Soon - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line("Your **{$planName}** trial will expire on **{$trialEndsAt}** (in 3 days).")
            ->line('To continue enjoying premium features, please upgrade to a paid subscription.')
            ->action('Upgrade Now', $upgradeUrl)
            ->line('If you have any questions, feel free to contact our support team.')
            ->line('Thank you for trying ' . config('app.name') . '!');
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
