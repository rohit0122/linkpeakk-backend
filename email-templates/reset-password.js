import { emailLayout, STYLES, PRIMARY_COLOR } from "./layout";
import { CONFIG } from "@/constants/config";

export const resetPasswordTemplate = (lpkVerifyToken) => {
  const resetUrl = `${CONFIG.SITE_URL}/reset-password?lpkVerifyToken=${lpkVerifyToken}`;

  return emailLayout({
    title: "Reset your password",
    previewText: "Follow the link below to reset your account password.",
    content: `
            <h1 style="${STYLES.h1}">
                Reset your password
            </h1>

            <p style="${STYLES.text}">
                We received a request to reset the password for your ${CONFIG.SITE_NAME} account.
                No worries, it happens!
            </p>

            <!-- CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                <tr>
                    <td align="center">
                        <a href="${resetUrl}" style="${STYLES.button}">
                            Reset My Password
                        </a>
                    </td>
                </tr>
            </table>

            <!-- Security Note -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                <tr>
                    <td style="background-color:#FFFBEB;padding:20px;border:1px solid #FEF3C7;">
                        <p style="margin:0;font-size:14px;color:#92400E;line-height:1.5;">
                            <strong>Security Note:</strong>
                            This link will expire in 1 hour. If you didn't request this change,
                            you can safely ignore this email and your password will remain unchanged.
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Fallback link -->
            <p style="${STYLES.text}; font-size:14px; color:#9CA3AF; border-top:1px solid #F3F4F6; padding-top:24px;">
                If the button above doesn't work, copy and paste this link into your browser:<br>
                <span style="color:${PRIMARY_COLOR}; word-break:break-all;">
                    ${resetUrl}
                </span>
            </p>
        `,
  });
};
