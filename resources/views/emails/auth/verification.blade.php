@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;">
    Verify your email address
</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    Thanks for signing up for {{ config('app.name') }}! We're excited to have you on board. Please confirm your email address by clicking the button below.
</p>

<div style="text-align:center;margin:32px 0;">
    <a href="{{ $verificationUrl }}" style="display:inline-block;background-color:#6D28D9;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;border-radius:6px;">
        Verify Email Address
    </a>
</div>

<p style="margin:0 0 32px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    This verification link is valid for <strong>24 hours</strong>. If you didn't create an account, you can safely ignore this email.
</p>

<div style="border-top:1px solid #E5E7EB;padding-top:24px;margin-top:32px;">
    <p style="margin:0 0 8px 0;font-size:14px;color:#9CA3AF;">
        If the button doesn't work, copy and paste this URL into your browser:
    </p>
    <p style="margin:0;font-size:13px;color:#6D28D9;word-break:break-all;line-height:1.4;">
        {{ $verificationUrl }}
    </p>
</div>
@endsection
