<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlanExpiryNotification extends Notification
{
    use Queueable;

    protected $user;
    protected $type; // 'warning' or 'expired'
    protected $daysRemaining;

    /**
     * Create a new notification instance.
     * 
     * @param $user
     * @param string $type
     * @param int|null $daysRemaining
     */
    public function __construct($user, $type = 'warning', $daysRemaining = null)
    {
        $this->user = $user;
        $this->type = $type;
        $this->daysRemaining = $daysRemaining;
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
        $planName = $this->user->plan->name ?? 'Premium';
        $renewUrl = config('app.public_url') . '/dashboard';

        $subject = "Your {$planName} Plan " . ($this->type === 'expired' ? 'has Expired' : "is Expiring in {$this->daysRemaining} days") . " - " . config('app.name');

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.billing.reminder', [
                'userName' => $notifiable->name ?? 'User',
                'trial' => false,
                'daysLeft' => $this->daysRemaining ?? 0,
                'isUrgent' => $this->type === 'expired' || ($this->daysRemaining < 3 && $this->daysRemaining !== null),
                'renewUrl' => $renewUrl,
                'title' => $this->type === 'expired' ? 'Plan Expired' : 'Plan Expiring Soon',
                'previewText' => $this->type === 'expired' ? "Your {$planName} plan has expired." : "Your {$planName} plan is expiring in {$this->daysRemaining} days.",
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'days_remaining' => $this->daysRemaining,
            'plan_id' => $this->user->plan_id,
        ];
    }
}
