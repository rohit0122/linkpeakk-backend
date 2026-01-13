@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;text-align:center;">
    Password Changed
</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;text-align:center;">
    Your password for {{ config('app.name') }} has been successfully updated.
</p>

<p style="margin:0 0 32px 0;font-size:16px;line-height:1.6;color:#4B5563;text-align:center;">
    You can now log in with your new password.
</p>

{{-- CTA Button --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
    <tr>
        <td align="center">
            <a href="{{ $loginUrl }}" style="display:inline-block;background-color:#6D28D9;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;border-radius:6px;">
                Login to Dashboard
            </a>
        </td>
    </tr>
</table>

<p style="margin:24px 0 0 0;font-size:14px;color:#6B7280;text-align:center;">
    If you did not perform this action, please contact support immediately.
</p>
@endsection
