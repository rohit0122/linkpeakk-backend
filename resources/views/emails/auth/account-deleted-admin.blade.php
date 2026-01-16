@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#DC2626;">Account Deleted Notification</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    A user has just permanently deleted their account from the platform.
</p>

<div style="background-color:#F9FAFB;padding:20px;border-radius:8px;margin-bottom:20px;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding-bottom:10px;font-size:14px;color:#6B7280;width:100px;">Name:</td>
            <td style="padding-bottom:10px;font-size:14px;font-weight:600;color:#111827;">{{ $name }}</td>
        </tr>
        <tr>
            <td style="padding-bottom:10px;font-size:14px;color:#6B7280;">Email:</td>
            <td style="padding-bottom:10px;font-size:14px;font-weight:600;color:#111827;">{{ $email }}</td>
        </tr>
        <tr>
            <td style="font-size:14px;color:#6B7280;">Deleted At:</td>
            <td style="font-size:14px;font-weight:600;color:#111827;">{{ $deletedAt }}</td>
        </tr>
    </table>
</div>

<p style="margin:0;font-size:14px;color:#6B7280;">
    This is an automated notification for administrative records.
</p>
@endsection
