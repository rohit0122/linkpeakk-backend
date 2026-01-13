

<?php $__env->startSection('content'); ?>
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;">Verify your email address</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    Thanks for signing up for <?php echo e(config('app.name')); ?>! We're excited to have you on board.
</p>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    Please confirm your email address by clicking the button below.
</p>

<div style="text-align:center;margin:32px 0;">
    <a href="<?php echo e($verificationUrl); ?>" style="display:inline-block;background-color:#6D28D9;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;border-radius:6px;">
        Verify Email Address
    </a>
</div>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    This verification link is valid for <strong>24 hours</strong>.
</p>

<p style="font-size:14px;color:#6B7280;margin-top:24px;">
    If you didn't create an account, you can safely ignore this email.
</p>

<p style="font-size:14px;color:#9CA3AF;border-top:1px solid #E5E7EB;padding-top:20px;margin-top:32px;">
    If the button doesn't work, copy and paste this URL into your browser:<br/>
    <span style="color:#6D28D9;word-break:break-all;">
        <?php echo e($verificationUrl); ?>

    </span>
</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('emails.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\linkpeakk\backend\resources\views/emails/auth/verification.blade.php ENDPATH**/ ?>