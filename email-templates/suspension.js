import { emailLayout, STYLES } from "./layout";
import { CONFIG } from "@/constants/config";

export const suspensionTemplate = ({ userName }) => {
    const renewUrl = `${CONFIG.SITE_URL}/suspended`;

    return emailLayout({
        title: "Action Required: Account Suspended",
        previewText: "Your subscription has expired. Renew now to reactivate your bio page.",
        content: `
            <h1 style="${STYLES.h1}; color:#DC2626; text-align:center;">
                Account Suspended
            </h1>

            <p style="${STYLES.text}">
                Hi ${userName},
            </p>

            <p style="${STYLES.text}">
                We were unable to renew your subscription, and as a result,
                your account has been temporarily suspended.
            </p>

            <!-- Suspension Details -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                <tr>
                    <td style="background-color:#FEF2F2;border:1px solid #FECACA;padding:20px;">
                        <p style="margin:0 0 12px 0;font-weight:600;color:#991B1B;">
                            What does this mean?
                        </p>

                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="16" valign="top" style="color:#7F1D1D;">•</td>
                                <td style="color:#7F1D1D;font-size:14px;">
                                    Your bio page is currently hidden
                                </td>
                            </tr>
                            <tr>
                                <td width="16" valign="top" style="color:#7F1D1D;">•</td>
                                <td style="color:#7F1D1D;font-size:14px;">
                                    Public visitors cannot see your links
                                </td>
                            </tr>
                            <tr>
                                <td width="16" valign="top" style="color:#7F1D1D;">•</td>
                                <td style="color:#7F1D1D;font-size:14px;">
                                    Analytics collection is paused
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <p style="${STYLES.text}">
                But don't worry! Your data is safe.
                You can reactivate your account instantly by updating your payment method.
            </p>

            <!-- CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:32px;">
                <tr>
                    <td align="center">
                        <a
                            href="${renewUrl}"
                            style="${STYLES.button}; background-color:#DC2626;"
                        >
                            Reactivate My Account
                        </a>
                    </td>
                </tr>
            </table>
        `
    });
};
