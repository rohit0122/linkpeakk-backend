import { emailLayout, STYLES } from "./layout";
import { CONFIG } from "@/constants/config";

export const passwordChangedTemplate = () => {
    const loginUrl = `${CONFIG.SITE_URL}/login`;

    return emailLayout({
        title: "Password Changed Successfully",
        previewText: "Your password has been successfully updated.",
        content: `
            <h1 style="${STYLES.h1}; text-align:center;">
                Password Changed
            </h1>

            <p style="${STYLES.text}; text-align:center;">
                Your password for ${CONFIG.SITE_NAME} has been successfully updated.
            </p>

            <p style="${STYLES.text}; text-align:center; margin-bottom:32px;">
                You can now log in with your new password.
            </p>

            <!-- CTA Button -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                <tr>
                    <td align="center">
                        <a href="${loginUrl}" style="${STYLES.button}">
                            Login to Dashboard
                        </a>
                    </td>
                </tr>
            </table>

            <p style="${STYLES.text}; font-size:14px; color:#6B7280; text-align:center; margin-top:24px;">
                If you did not perform this action, please contact support immediately.
            </p>
        `
    });
};
