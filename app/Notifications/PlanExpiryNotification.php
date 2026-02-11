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
        $appName = config('app.name');
        $renewUrl = config('app.public_url') . '/dashboard';

        if ($this->type === 'expired') {
            return (new MailMessage)
                ->subject("Your {$planName} Plan has Expired - {$appName}")
                ->greeting("Hello {$notifiable->name}!")
                ->line("Your **{$planName}** plan has expired.")
                ->line("Your account has been transitioned to the **Free** plan.")
                ->line("To regain access to all premium features, you can renew your plan at any time.")
                ->action('Renew Now', $renewUrl)
                ->line("Thank you for using {$appName}!");
        }

        // Warning messages (7, 5, 2 days)
        $subject = "Your {$planName} Plan is Expiring in {$this->daysRemaining} Days - $appName";
        
        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your **{$planName}** plan will expire in **{$this->daysRemaining} days**.")
            ->line("Renew now to ensure uninterrupted access to all your premium features.")
            ->action('Renew Plan', $renewUrl)
            ->line("If you have already renewed, please ignore this email.")
            ->line("Thank you for using {$appName}!");
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
