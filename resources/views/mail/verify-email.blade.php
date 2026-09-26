<x-mail::message>
@if ($user->first_name)
Hi {{ $user->first_name }},
@else
Hi,
@endif

Welcome to **{{ $appName }}**. Enter this code in the tab where you signed up to verify your email — you don’t need to leave that screen.

<div style="margin: 28px 0; text-align: center;">
<div style="display: inline-block; padding: 16px 28px; border-radius: 16px; background: #F4F6FA; border: 1px solid rgba(11,31,58,0.08);">
<span style="font-size: 32px; font-weight: 800; letter-spacing: 0.28em; color: #0B1F3A; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;">
{{ $code }}
</span>
</div>
<p style="margin: 12px 0 0; font-size: 13px; color: #64748B;">
Expires in {{ $expiresMinutes }} minutes · one-time use
</p>
</div>

Prefer a shortcut? You can also confirm with one tap:

<x-mail::button :url="$verifyUrl" color="primary">
Verify my email
</x-mail::button>

If you didn’t create a {{ $appName }} account, you can ignore this email.

Thanks,  
The {{ $appName }} team

<x-slot:subcopy>
If the button doesn’t open, paste this link in your browser:<br>
[{{ $verifyUrl }}]({{ $verifyUrl }})
</x-slot:subcopy>
</x-mail::message>
