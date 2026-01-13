<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewalUpcoming extends Notification
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
    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $daysLeft = now()->diffInDays($this->subscription->current_period_end);
        $isUrgent = $daysLeft <= 3;
        $trial = $this->subscription->plan->name === 'Trial' || str_contains(strtolower($this->subscription->plan->name), 'trial');
        $renewUrl = config('app.public_url') . '/dashboard';

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject(($trial ? 'Your trial ends in ' : 'Subscription expires in ') . $daysLeft . ' days')
            ->view('emails.billing.reminder', [
                'userName' => $notifiable->name,
                'daysLeft' => $daysLeft,
                'isUrgent' => $isUrgent,
                'trial' => $trial,
                'renewUrl' => $renewUrl,
                'title' => $trial ? "Your trial ends in {$daysLeft} days!" : "Subscription expires in {$daysLeft} days",
                'previewText' => "Don't lose your premium features and data. Renew now.",
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
