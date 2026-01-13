@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;">
    Don't lose your access!
</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    Hi {{ $userName }},
</p>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    This is a friendly reminder that your
    <strong>{{ $trial ? 'Trial' : 'Premium Subscription' }}</strong>
    expires in:
</p>

{{-- Countdown --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:32px 0;">
    <tr>
        <td align="center">
            <div style="font-size:48px;font-weight:800;color:{{ $isUrgent ? '#DC2626' : '#6D28D9' }};line-height:1;">
                {{ $daysLeft }}
            </div>
            <div style="font-size:18px;color:#6B7280;margin-top:6px;">
                DAYS
            </div>
        </td>
    </tr>
</table>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    To keep your bio page live, your analytics tracking, and all your premium customizations,
    please renew your plan before time runs out.
</p>

{{-- CTA --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:32px;">
    <tr>
        <td align="center">
            <a
                href="{{ $renewUrl }}"
                style="display:inline-block;background-color:{{ $isUrgent ? '#DC2626' : '#6D28D9' }};color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;border-radius:6px;"
            >
                {{ $trial ? 'Secure My Account' : 'Renew Subscription' }}
            </a>
        </td>
    </tr>
</table>
@endsection
