<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo e($title ?? 'LinkPeakK.'); ?></title>
</head>

<body style="margin:0;padding:0;background-color:#F3F4F6;">
    
    <?php if(isset($previewText)): ?>
    <span style="display:none;max-height:0;overflow:hidden;"><?php echo e($previewText); ?></span>
    <?php endif; ?>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background-color:#F3F4F6;padding:40px 0;">
        <tr>
            <td align="center">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;border-collapse:collapse;">
                    
                    
                    <tr>
                        <td style="background-color:#6D28D9;padding:32px;text-align:center;">
                            <a href="<?php echo e(config('app.public_url')); ?>" style="font-size:28px;font-weight:800;color:#ffffff;text-decoration:none;display:inline-block;">
                                <?php echo e(config('app.name')); ?><span style="color:#A78BFA;">.</span>
                            </a>
                        </td>
                    </tr>

                    
                    <tr>
                        <td style="padding:40px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
                            <?php echo $__env->yieldContent('content'); ?>
                        </td>
                    </tr>

                    
                    <tr>
                        <td style="padding:32px;background-color:#F9FAFB;text-align:center;font-size:13px;color:#6B7280;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
                            <p style="margin:0 0 8px 0;">© <?php echo e(date('Y')); ?> <?php echo e(config('app.name')); ?>. All rights reserved.</p>
                            <p style="margin:0 0 16px 0;">Empowering creators to peak their online presence.</p>

                            <p style="margin:0;">
                                <a href="<?php echo e(config('app.public_url')); ?>/dashboard" style="color:#6D28D9;text-decoration:underline;">Dashboard</a> ·
                                <a href="<?php echo e(config('app.public_url')); ?>/terms" style="color:#6D28D9;text-decoration:underline;">Terms</a> ·
                                <a href="<?php echo e(config('app.public_url')); ?>/privacy" style="color:#6D28D9;text-decoration:underline;">Privacy</a>
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>
<?php /**PATH C:\wamp64\www\linkpeakk\backend\resources\views/emails/layout.blade.php ENDPATH**/ ?>