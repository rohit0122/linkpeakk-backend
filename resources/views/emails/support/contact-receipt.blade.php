@extends('emails.layout')

@section('content')
<h1 style="margin:0 0 20px 0;font-size:24px;font-weight:700;color:#111827;">
    Thanks for getting in touch!
</h1>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    Hi {{ $name }},
</p>

<p style="margin:0 0 20px 0;font-size:16px;line-height:1.6;color:#4B5563;">
    We have received your message regarding "<strong>{{ $subject }}</strong>". Our team will review your inquiry and get back to you as soon as possible.
</p>

@if(isset($messageBody) && $messageBody)
<div style="background-color:#F3F4F6;padding:16px;font-size:14px;color:#4B5563;border-radius:4px;margin-bottom:20px;font-style:italic;">
    "{{ $messageBody }}"
</div>
@endif

<hr style="border:none;border-top:1px solid #E5E7EB;margin:32px 0;" />

<p style="margin:0;font-size:14px;color:#6B7280;line-height:1.5;">
    Depending on the volume of inquiries, response times may vary. We appreciate your patience and look forward to helping you.
</p>
@endsection
