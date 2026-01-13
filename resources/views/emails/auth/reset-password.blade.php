@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;">
    Reset your password
</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    We received a request to reset the password for your {{ config('app.name') }} account.
    No worries, it happens!
</p>

{{-- CTA --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
    <tr>
        <td align="center">
            <a href="{{ $resetUrl }}" style="display:inline-block;background-color:#6D28D9;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;border-radius:6px;">
                Reset My Password
            </a>
        </td>
    </tr>
</table>

{{-- Security Note --}}
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

{{-- Fallback link --}}
<p style="margin:0;font-size:14px;color:#9CA3AF;border-top:1px solid #F3F4F6;padding-top:24px;">
    If the button above doesn't work, copy and paste this link into your browser:<br>
    <span style="color:#6D28D9;word-break:break-all;">
        {{ $resetUrl }}
    </span>
</p>
@endsection
