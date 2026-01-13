import { emailLayout, STYLES, PRIMARY_COLOR } from "./layout";
import { CONFIG } from "@/constants/config";

export const contactAdminTemplate = ({ name, email, subject, message }) => {
    return emailLayout({
        title: `New Message: ${subject}`,
        previewText: `New contact form submission from ${name}`,
        content: `
            <h1 style="${STYLES.h1}">New Contact Form Submission</h1>

            <!-- Sender Details -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                <tr>
                    <td style="background-color:#F9FAFB;padding:20px;">
                        <p style="margin:0 0 12px 0;font-size:13px;font-weight:600;color:#6B7280;">
                            SENDER DETAILS
                        </p>

                        <p style="margin:0 0 6px 0;font-size:16px;font-weight:600;color:#111827;">
                            ${name}
                        </p>

                        <p style="margin:0;font-size:14px;">
                            <a href="mailto:${email}" style="color:${PRIMARY_COLOR};text-decoration:none;">
                                ${email}
                            </a>
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Subject -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                <tr>
                    <td>
                        <p style="margin:0 0 8px 0;font-size:13px;font-weight:600;color:#374151;">
                            SUBJECT
                        </p>
                        <div style="background-color:#F3F4F6;padding:14px;font-size:15px;color:#111827;">
                            ${subject}
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Message -->
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <p style="margin:0 0 8px 0;font-size:13px;font-weight:600;color:#374151;">
                            MESSAGE
                        </p>
                        <div style="background-color:#F3F4F6;padding:16px;font-size:15px;color:#374151;line-height:1.6;white-space:pre-wrap;">
                            ${message}
                        </div>
                    </td>
                </tr>
            </table>
        `
    });
};

export const contactReceiptTemplate = ({ name, subject }) => {
    return emailLayout({
        title: `We've received your message`,
        previewText: `Thanks for contacting ${CONFIG.SITE_NAME}`,
        content: `
            <h1 style="${STYLES.h1}">Thanks for getting in touch!</h1>
            <p style="${STYLES.p}">
                Hi ${name},
            </p>
            <p style="${STYLES.p}">
                We have received your message regarding "<strong>${subject}</strong>".
            </p>
            <p style="${STYLES.p}">
                Our team will review your inquiry and get back to you as soon as possible.
            </p>
            <hr style="${STYLES.hr}" />
            <p style="${STYLES.p}font-size:14px;color:#6B7280;">
                Depending on the volume of inquiries, response times may vary. We appreciate your patience.
            </p>
        `
    });
};
