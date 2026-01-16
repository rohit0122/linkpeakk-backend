@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;">It's hard to say goodbye.</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    Hi {{ $name }},
</p>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    We've successfully processed your request to delete your account. All your data has been permanently removed as requested.
</p>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    While we're sad to see you go, we respect your decision and hope <strong>LinkPeakK.</strong> helped you reach new heights while you were with us.
</p>

<div style="background-color:#F9FAFB;padding:20px;border-radius:8px;margin-bottom:20px;text-align:center;">
    <p style="margin:0;font-size:16px;font-weight:600;color:#111827;">
        "Your 'peak' will always be here if you ever decide to start your journey again."
    </p>
</div>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    We'd love to welcome you back anytime.
</p>

<p style="margin:0;font-size:16px;font-weight:700;color:#111827;">
    See you soon,<br>
    The LinkPeakK. Team
</p>
@endsection
