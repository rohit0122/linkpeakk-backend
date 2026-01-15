<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'LinkPeakK.' }}</title>
</head>

<body style="margin:0;padding:0;background-color:#F3F4F6;">
    {{-- Preview Text (hidden) --}}
    @if(isset($previewText))
    <span style="display:none;max-height:0;overflow:hidden;">{{ $previewText }}</span>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;background-color:#F3F4F6;padding:40px 0;">
        <tr>
            <td align="center">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background-color:#ffffff;border-collapse:collapse;">
                    
                    {{-- HEADER --}}
                    <tr>
                        <td style="background-color:#6D28D9;padding:32px;text-align:center;">
                            <a href="{{ config('app.public_url') }}" style="font-size:28px;font-weight:800;color:#ffffff;text-decoration:none;display:inline-block;">
                                {{ config('app.name') }}<span style="color:#A78BFA;">.</span>
                            </a>
                        </td>
                    </tr>

                    {{-- CONTENT --}}
                    <tr>
                        <td style="padding:40px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="padding:32px;background-color:#F9FAFB;text-align:center;font-size:13px;color:#6B7280;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
                            <p style="margin:0 0 8px 0;">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p style="margin:0 0 16px 0;">The peak of your digital identity.</p>

                            <p style="margin:0;">
                                <a href="{{ config('app.public_url') }}/dashboard" style="color:#6D28D9;text-decoration:underline;">Dashboard</a> ·
                                <a href="{{ config('app.public_url') }}/terms-and-conditions" style="color:#6D28D9;text-decoration:underline;">Terms</a> ·
                                <a href="{{ config('app.public_url') }}/privacy-policy" style="color:#6D28D9;text-decoration:underline;">Privacy</a> ·
                                <a href="{{ config('app.public_url') }}/cookies-policy" style="color:#6D28D9;text-decoration:underline;">Cookies</a> ·
                                <a href="{{ config('app.public_url') }}/contact-us" style="color:#6D28D9;text-decoration:underline;">Contact</a>
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>
