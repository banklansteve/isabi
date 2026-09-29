<x-mail::message>
@if ($user->first_name)
Hi {{ $user->first_name }},
@else
Hi,
@endif

Welcome to **{{ $appName }}**. Confirm your email with the code below to unlock logging jobs and sending client review requests.

<div style="margin: 32px 0; text-align: center;">
<div style="display: inline-block; padding: 20px 36px; border-radius: 20px; background: linear-gradient(180deg, #F7FAFF 0%, #EEF3FB 100%); border: 1px solid rgba(26,79,181,0.12); box-shadow: 0 12px 32px -18px rgba(26,79,181,0.5);">
<span style="display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; color: #64748B; margin-bottom: 12px;">
Verification code
</span>
<span style="font-size: 36px; font-weight: 800; letter-spacing: 0.36em; color: #0B1F3A; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;">
{{ $code }}
</span>
</div>
<p style="margin: 14px 0 0; font-size: 13px; color: #64748B;">
Expires in {{ $expiresMinutes }} minutes · single use
</p>
</div>

Type it on the screen where you left off. Prefer one tap?

<x-mail::button :url="$verifyUrl" color="primary">
Confirm email
</x-mail::button>

If you didn’t create a {{ $appName }} account, you can ignore this email — nothing else will happen.

Thanks,<br>
The {{ $appName }} team

<x-slot:subcopy>
Button not working? Paste this into your browser:<br>
[{{ $verifyUrl }}]({{ $verifyUrl }})
</x-slot:subcopy>
</x-mail::message>
