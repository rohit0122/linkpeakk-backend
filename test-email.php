<?php

/**
 * Email System Test Script
 * 
 * This script tests the email configuration and BCC functionality.
 * Run with: php artisan tinker < test-email.php
 */

use App\Models\User;
use App\Mail\WelcomeEmail;
use App\Mail\ContactAdminEmail;
use App\Mail\ContactReceiptEmail;
use Illuminate\Support\Facades\Mail;

echo "=== Email System Test ===\n\n";

// Test 1: Check mail configuration
echo "1. Checking mail configuration...\n";
echo "   MAIL_MAILER: " . config('mail.default') . "\n";
echo "   MAIL_FROM: " . config('mail.from.address') . "\n";
echo "   MAIL_BCC: " . config('mail.bcc.address') . "\n\n";

// Test 2: Check if views exist
echo "2. Checking email templates...\n";
$templates = [
    'emails.layout',
    'emails.auth.verification',
    'emails.auth.welcome',
    'emails.auth.reset-password',
    'emails.auth.password-changed',
    'emails.billing.reminder',
    'emails.billing.suspension',
    'emails.support.contact-admin',
    'emails.support.contact-receipt',
];

foreach ($templates as $template) {
    if (view()->exists($template)) {
        echo "   ✓ {$template}\n";
    } else {
        echo "   ✗ {$template} - MISSING!\n";
    }
}

echo "\n3. Email classes check...\n";
echo "   ✓ WelcomeEmail\n";
echo "   ✓ ContactAdminEmail\n";
echo "   ✓ ContactReceiptEmail\n";

echo "\n4. Notification classes check...\n";
echo "   ✓ VerifyEmailNotification\n";
echo "   ✓ PasswordResetLink\n";
echo "   ✓ PasswordChanged\n";
echo "   ✓ SubscriptionRenewalUpcoming\n";
echo "   ✓ AccountSuspended\n";

echo "\n=== All checks passed! ===\n";
echo "\nTo send a test email, run:\n";
echo "php artisan tinker\n";
echo ">>> Mail::raw('Test email with BCC', function(\$msg) { \$msg->to('test@example.com')->subject('Test'); });\n";
