import { emailLayout, STYLES, PRIMARY_COLOR } from "./layout";
import { CONFIG } from "@/constants/config";

export const welcomeTemplate = ({ name }) => {
    const dashboardUrl = `${CONFIG.SITE_URL}/dashboard`;

    return emailLayout({
        title: `Welcome to ${CONFIG.SITE_NAME}!`,
        previewText: "Your account is verified. Let's build your amazing bio link page.",
        content: `
            <h1 style="${STYLES.h1}">
                Welcome aboard, ${name}!
            </h1>

            <p style="${STYLES.text}">
                Your account is officially verified. It's time to create a bio page that works as hard as you do.
            </p>

            <p style="${STYLES.text}; font-weight:600; color:#111827;">
                Here's how to get started in 3 minutes:
            </p>

            <!-- Steps -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                <tr>
                    <td width="32" valign="top" align="center"
                        style="background-color:#EDE9FE;color:${PRIMARY_COLOR};font-weight:700;font-size:14px;">
                        1
                    </td>
                    <td style="padding-left:12px;font-size:16px;color:#4B5563;">
                        Claim your unique <strong>@username</strong>
                    </td>
                </tr>
                <tr><td colspan="2" height="12"></td></tr>
                <tr>
                    <td width="32" valign="top" align="center"
                        style="background-color:#EDE9FE;color:${PRIMARY_COLOR};font-weight:700;font-size:14px;">
                        2
                    </td>
                    <td style="padding-left:12px;font-size:16px;color:#4B5563;">
                        Add your most important links & social icons
                    </td>
                </tr>
                <tr><td colspan="2" height="12"></td></tr>
                <tr>
                    <td width="32" valign="top" align="center"
                        style="background-color:#EDE9FE;color:${PRIMARY_COLOR};font-weight:700;font-size:14px;">
                        3
                    </td>
                    <td style="padding-left:12px;font-size:16px;color:#4B5563;">
                        Pick a theme or customize colors to match your brand
                    </td>
                </tr>
            </table>

            <!-- CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                <tr>
                    <td align="center">
                        <a href="${dashboardUrl}" style="${STYLES.button}">
                            Create My Bio Page
                        </a>
                    </td>
                </tr>
            </table>

            <p style="${STYLES.text}; margin-top:32px;">
                We're thrilled to have you here. If you need any help, just reply to this email!
            </p>
        `
    });
};
