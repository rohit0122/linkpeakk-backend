@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;">
    {{ $isUrgent ? 'Action Required: Plan Expiring' : 'Your Plan is Expiring Soon' }}
</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    Hi {{ $userName }},
</p>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    This is a friendly reminder that your <strong>{{ $trial ? 'Trial' : 'Premium Access' }}</strong> is set to expire in:
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

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#111827;font-weight:600;">
    Don't lose your premium features:
</p>

{{-- Features/Steps Style --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
    <tr>
        <td width="32" valign="top" align="center" style="background-color:#EDE9FE;color:#6D28D9;font-weight:700;font-size:14px;padding:6px;border-radius:4px;">
            ✓
        </td>
        <td style="padding-left:12px;font-size:16px;color:#4B5563;">
            Keep your unique bio link active
        </td>
    </tr>
    <tr><td colspan="2" height="12"></td></tr>
    <tr>
        <td width="32" valign="top" align="center" style="background-color:#EDE9FE;color:#6D28D9;font-weight:700;font-size:14px;padding:6px;border-radius:4px;">
            ✓
        </td>
        <td style="padding-left:12px;font-size:16px;color:#4B5563;">
            Maintain detailed analytics & insights
        </td>
    </tr>
    <tr><td colspan="2" height="12"></td></tr>
    <tr>
        <td width="32" valign="top" align="center" style="background-color:#EDE9FE;color:#6D28D9;font-weight:700;font-size:14px;padding:6px;border-radius:4px;">
            ✓
        </td>
        <td style="padding-left:12px;font-size:16px;color:#4B5563;">
            Access all premium themes & customizations
        </td>
    </tr>
</table>

{{-- CTA --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
    <tr>
        <td align="center">
            <a href="{{ $renewUrl }}" style="display:inline-block;background-color:#6D28D9;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;border-radius:6px;">
                {{ $trial ? 'Secure My Account' : 'Extend Access' }}
            </a>
        </td>
    </tr>
</table>

<p style="margin:0;font-size:16px;line-height:1.6;color:#4B5563;">
    To ensure uninterrupted service, please renew your plan before it expires. If you have any questions, we're here to help!
</p>
@endsection
