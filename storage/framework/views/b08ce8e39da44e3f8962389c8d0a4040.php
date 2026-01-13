

<?php $__env->startSection('content'); ?>
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;">
    Welcome aboard, <?php echo e($name); ?>!
</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    Your account is officially verified. It's time to create a bio page that works as hard as you do.
</p>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#111827;font-weight:600;">
    Here's how to get started in 3 minutes:
</p>


<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
    <tr>
        <td width="32" valign="top" align="center"
            style="background-color:#EDE9FE;color:#6D28D9;font-weight:700;font-size:14px;padding:6px;">
            1
        </td>
        <td style="padding-left:12px;font-size:16px;color:#4B5563;">
            Claim your unique <strong>@username</strong>
        </td>
    </tr>
    <tr><td colspan="2" height="12"></td></tr>
    <tr>
        <td width="32" valign="top" align="center"
            style="background-color:#EDE9FE;color:#6D28D9;font-weight:700;font-size:14px;padding:6px;">
            2
        </td>
        <td style="padding-left:12px;font-size:16px;color:#4B5563;">
            Add your most important links & social icons
        </td>
    </tr>
    <tr><td colspan="2" height="12"></td></tr>
    <tr>
        <td width="32" valign="top" align="center"
            style="background-color:#EDE9FE;color:#6D28D9;font-weight:700;font-size:14px;padding:6px;">
            3
        </td>
        <td style="padding-left:12px;font-size:16px;color:#4B5563;">
            Pick a theme or customize colors to match your brand
        </td>
    </tr>
</table>


<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
    <tr>
        <td align="center">
            <a href="<?php echo e($dashboardUrl); ?>" style="display:inline-block;background-color:#6D28D9;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;border-radius:6px;">
                Create My Bio Page
            </a>
        </td>
    </tr>
</table>

<p style="margin:32px 0 0 0;font-size:16px;line-height:1.6;color:#4B5563;">
    We're thrilled to have you here. If you need any help, just reply to this email!
</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('emails.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\linkpeakk\backend\resources\views/emails/auth/welcome.blade.php ENDPATH**/ ?>