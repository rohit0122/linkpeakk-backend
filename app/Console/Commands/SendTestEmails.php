<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Mail\WelcomeEmail;
use App\Mail\AccountDeletedUserEmail;
use App\Mail\AccountDeletedAdminEmail;
use App\Mail\ContactReceiptEmail;
use App\Mail\ContactAdminEmail;
use App\Notifications\WelcomeNotification;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\PasswordResetLink;
use App\Notifications\PasswordChanged;
use App\Notifications\TrialExpiringNotification;
use App\Notifications\PlanExpiryNotification;
use App\Notifications\AccountSuspended;

class SendTestEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:send-test-notifications {email=rohit.seed@gmail.com} {--only-billing : Only send trial and plan expiry notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send all notification emails with dummy data to a specific email for testing UI/UX.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetEmail = $this->argument('email');
        $this->info("Sending test emails to: $targetEmail");

        // Create a dummy user for notifications
        $user = new User();
        $user->name = 'Test User';
        $user->email = $targetEmail;

        $name = $user->name;
        $email = $user->email;

        if ($this->option('only-billing')) {
            goto notifications;
        }

        // 1. Welcome Email (Mailable)
        $this->comment('Sending: WelcomeEmail...');
        Mail::to($targetEmail)->send(new WelcomeEmail($name));

        // 2. Account Deleted User (Mailable)
        $this->comment('Sending: AccountDeletedUserEmail...');
        Mail::to($targetEmail)->send(new AccountDeletedUserEmail($name));

        // 3. Account Deleted Admin (Mailable)
        $this->comment('Sending: AccountDeletedAdminEmail...');
        Mail::to($targetEmail)->send(new AccountDeletedAdminEmail($name, $email, now()->toDateTimeString()));

        // 4. Contact Receipt (Mailable)
        $this->comment('Sending: ContactReceiptEmail...');
        Mail::to($targetEmail)->send(new ContactReceiptEmail($name, 'Testing UI Consistency', 'This is a test message.'));

        // 5. Contact Admin (Mailable)
        $this->comment('Sending: ContactAdminEmail...');
        Mail::to($targetEmail)->send(new ContactAdminEmail($name, $email, 'Testing Admin UI', 'This is a test message from a user.'));

        notifications:
        // Notifications
        $this->info('Sending Notifications via Mail channel...');

        // 6. Welcome Notification
        if (!$this->option('only-billing')) {
            $this->comment('Sending: WelcomeNotification...');
            $user->notify(new WelcomeNotification());

            // 7. Verify Email Notification
            $this->comment('Sending: VerifyEmailNotification...');
            $user->notify(new VerifyEmailNotification());

            // 8. Password Reset Link
            $this->comment('Sending: PasswordResetLink...');
            $user->notify(new PasswordResetLink('test-token'));

            // 9. Password Changed
            $this->comment('Sending: PasswordChanged...');
            $user->notify(new PasswordChanged());
        }

        // 10. Trial Expiring (Billing Reminder)
        $this->comment('Sending: TrialExpiringNotification (3 days left)...');
        // Simulate subscription for trial
        $user->notify(new TrialExpiringNotification((object)['trial_ends_at' => now()->addDays(3)], 3));

        // 11. Plan Expiry (Billing Reminder)
        $this->comment('Sending: PlanExpiryNotification (7 days left)...');
        $user->notify(new PlanExpiryNotification($user, 'warning', 7));

        if (!$this->option('only-billing')) {
            // 12. Account Suspended
            $this->comment('Sending: AccountSuspended...');
            $user->notify(new AccountSuspended());
        }

        $this->info('All test emails have been dispatched!');
    }
}
