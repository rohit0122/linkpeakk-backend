import { CONFIG } from "@/constants/config";

export const PRIMARY_COLOR = "#6D28D9"; // Brand Purple
export const TEXT_COLOR = "#374151";
export const LIGHT_BG = "#F3F4F6";

/**
 * INLINE STYLES (Email-safe)
 */
export const STYLES = {
    body: `margin:0;padding:0;background-color:${LIGHT_BG};`,
    container: `width:100%;background-color:${LIGHT_BG};padding:40px 0;`,
    table: `max-width:600px;margin:0 auto;background-color:#ffffff;border-collapse:collapse;`,
    header: `background-color:${PRIMARY_COLOR};padding:32px;text-align:center;`,
    logo: `font-size:28px;font-weight:800;color:#ffffff;text-decoration:none;display:inline-block;`,
    logoDot: `color:#A78BFA;`,
    content: `padding:40px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;`,
    footer: `padding:32px;background-color:#F9FAFB;text-align:center;font-size:13px;color:#6B7280;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;`,
    h1: `margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;`,
    text: `margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;`,
    buttonWrap: `text-align:center;margin:32px 0;`,
    button: `display:inline-block;background-color:${PRIMARY_COLOR};color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;border-radius:6px;`,
    link: `color:${PRIMARY_COLOR};text-decoration:underline;`,
    dividerText: `font-size:14px;color:#9CA3AF;border-top:1px solid #E5E7EB;padding-top:20px;margin-top:32px;`,
    smallText: `font-size:14px;color:#6B7280;margin-top:24px;`
};

/**
 * MAIN EMAIL LAYOUT
 */
export const emailLayout = ({ title, previewText, content }) => `
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>${title}</title>
</head>

<body style="${STYLES.body}">
<span style="display:none;max-height:0;overflow:hidden;">${previewText}</span>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="${STYLES.container}">
<tr>
<td align="center">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="${STYLES.table}">
    
    <!-- HEADER -->
    <tr>
        <td style="${STYLES.header}">
            <a href="${CONFIG.SITE_URL}" style="${STYLES.logo}">
                ${CONFIG.SITE_NAME}<span style="${STYLES.logoDot}">.</span>
            </a>
        </td>
    </tr>

    <!-- CONTENT -->
    <tr>
        <td style="${STYLES.content}">
            ${content}
        </td>
    </tr>

    <!-- FOOTER -->
    <tr>
        <td style="${STYLES.footer}">
            <p style="margin:0 0 8px 0;">© ${new Date().getFullYear()} ${CONFIG.SITE_NAME}. All rights reserved.</p>
            <p style="margin:0 0 16px 0;">Empowering creators to peak their online presence.</p>

            <p style="margin:0;">
                <a href="${CONFIG.SITE_URL}/dashboard" style="${STYLES.link}">Dashboard</a> ·
                <a href="${CONFIG.SITE_URL}/terms" style="${STYLES.link}">Terms</a> ·
                <a href="${CONFIG.SITE_URL}/privacy" style="${STYLES.link}">Privacy</a>
            </p>
        </td>
    </tr>

</table>

</td>
</tr>
</table>
</body>
</html>
`;