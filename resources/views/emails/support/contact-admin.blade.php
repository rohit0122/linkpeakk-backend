@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;">New Contact Form Submission</h1>

{{-- Sender Details --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
    <tr>
        <td style="background-color:#F9FAFB;padding:20px;">
            <p style="margin:0 0 12px 0;font-size:13px;font-weight:600;color:#6B7280;">
                SENDER DETAILS
            </p>

            <p style="margin:0 0 6px 0;font-size:16px;font-weight:600;color:#111827;">
                {{ $name }}
            </p>

            <p style="margin:0;font-size:14px;">
                <a href="mailto:{{ $email }}" style="color:#6D28D9;text-decoration:none;">
                    {{ $email }}
                </a>
            </p>
        </td>
    </tr>
</table>

{{-- Subject --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
    <tr>
        <td>
            <p style="margin:0 0 8px 0;font-size:13px;font-weight:600;color:#374151;">
                SUBJECT
            </p>
            <div style="background-color:#F3F4F6;padding:14px;font-size:15px;color:#111827;">
                {{ $subject }}
            </div>
        </td>
    </tr>
</table>

{{-- Message --}}
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <p style="margin:0 0 8px 0;font-size:13px;font-weight:600;color:#374151;">
                MESSAGE
            </p>
            <div style="background-color:#F3F4F6;padding:16px;font-size:15px;color:#374151;line-height:1.6;white-space:pre-wrap;">{{ $message }}</div>
        </td>
    </tr>
</table>
@endsection
