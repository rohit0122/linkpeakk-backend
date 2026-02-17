@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;">
    Password Changed Successfully
</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    Your password for {{ config('app.name') }} has been successfully updated. You can now log in with your new password.
</p>

{{-- CTA Button --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:32px 0;">
    <tr>
        <td align="center">
            <a href="{{ $loginUrl }}" style="display:inline-block;background-color:#6D28D9;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;border-radius:6px;">
                Login to Dashboard
            </a>
        </td>
    </tr>
</table>

<p style="margin:32px 0 0 0;font-size:14px;color:#6B7280;line-height:1.5;">
    If you did not perform this action, please <strong>contact support immediately</strong> to secure your account.
</p>
@endsection
