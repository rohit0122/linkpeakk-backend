@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#DC2626;">
    Account Suspended
</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    Hi {{ $userName }},
</p>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    We were unable to renew your subscription, and as a result, your account has been temporarily suspended.
</p>

{{-- Suspension Details --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin:32px 0;">
    <tr>
        <td style="background-color:#FEF2F2;border:1px solid #FECACA;padding:24px;border-radius:8px;">
            <p style="margin:0 0 16px 0;font-weight:600;color:#991B1B;font-size:16px;">
                What does this mean?
            </p>

            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="24" valign="top" style="color:#DC2626;font-size:18px;line-height:1;">•</td>
                    <td style="color:#7F1D1D;font-size:15px;line-height:1.5;padding-bottom:12px;">
                        Your bio page is currently hidden from public view
                    </td>
                </tr>
                <tr>
                    <td width="24" valign="top" style="color:#DC2626;font-size:18px;line-height:1;">•</td>
                    <td style="color:#7F1D1D;font-size:15px;line-height:1.5;padding-bottom:12px;">
                        Public visitors cannot see your links or contact you
                    </td>
                </tr>
                <tr>
                    <td width="24" valign="top" style="color:#DC2626;font-size:18px;line-height:1;">•</td>
                    <td style="color:#7F1D1D;font-size:15px;line-height:1.5;">
                        Analytics collection has been paused
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<p style="margin:0 0 32px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    But don't worry! Your data is safe. You can reactivate your account instantly by updating your payment method and renewing your subscription.
</p>

{{-- CTA --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
    <tr>
        <td align="center">
            <a
                href="{{ $renewUrl }}"
                style="display:inline-block;background-color:#DC2626;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 32px;border-radius:6px;"
            >
                Reactivate My Account
            </a>
        </td>
    </tr>
</table>
@endsection
