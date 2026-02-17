@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;">
    Reset your password
</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    We received a request to reset the password for your {{ config('app.name') }} account. No worries, it happens! Click the button below to choose a new one.
</p>

{{-- CTA --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:32px 0;">
    <tr>
        <td align="center">
            <a href="{{ $resetUrl }}" style="display:inline-block;background-color:#6D28D9;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;border-radius:6px;">
                Reset My Password
            </a>
        </td>
    </tr>
</table>

{{-- Security Note --}}
<div style="background-color:#FFFBEB;padding:24px;border-radius:8px;border:1px solid #FEF3C7;margin-bottom:32px;">
    <p style="margin:0;font-size:14px;color:#92400E;line-height:1.6;">
        <strong style="color:#B45309;">Security Note:</strong>
        This link will expire in 1 hour. If you didn't request this change, you can safely ignore this email and your password will remain unchanged.
    </p>
</div>

{{-- Fallback link --}}
<div style="border-top:1px solid #F3F4F6;padding-top:24px;">
    <p style="margin:0 0 8px 0;font-size:14px;color:#9CA3AF;">
        If the button above doesn't work, copy and paste this link into your browser:
    </p>
    <p style="margin:0;font-size:13px;color:#6D28D9;word-break:break-all;line-height:1.4;">
        {{ $resetUrl }}
    </p>
</div>
@endsection
