import { emailLayout, STYLES, PRIMARY_COLOR } from "./layout";
import { CONFIG } from "@/constants/config";

export const verificationTemplate = (lpkVerifyToken) => {
  const verifyUrl = `${CONFIG.SITE_URL}/verify?lpkVerifyToken=${lpkVerifyToken}`;

  return emailLayout({
    title: "Verify your email",
    previewText: "Verify your email address to complete your registration.",
    content: `
            <h1 style="${STYLES.h1}">Verify your email address</h1>

            <p style="${STYLES.text}">
                Thanks for signing up for ${CONFIG.SITE_NAME}! We're excited to have you on board.
            </p>

            <p style="${STYLES.text}">
                Please confirm your email address by clicking the button below.
            </p>

            <div style="${STYLES.buttonWrap}">
                <a href="${verifyUrl}" style="${STYLES.button}">
                    Verify Email Address
                </a>
            </div>

            <p style="${STYLES.text}">
                This verification link is valid for <strong>24 hours</strong>.
            </p>

            <p style="${STYLES.smallText}">
                If you didn’t create an account, you can safely ignore this email.
            </p>

            <p style="${STYLES.dividerText}">
                If the button doesn’t work, copy and paste this URL into your browser:<br/>
                <span style="color:${PRIMARY_COLOR};word-break:break-all;">
                    ${verifyUrl}
                </span>
            </p>
        `,
  });
};
