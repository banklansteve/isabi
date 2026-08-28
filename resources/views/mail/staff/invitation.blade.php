<x-mail::message>
@if ($invitee->first_name)
Hi {{ $invitee->first_name }},
@else
Hi,
@endif

You've officially been added as part of the **{{ $appName }}** operations team — welcome aboard.

{{ $appName }} helps Nigerian artisans and freelancers build a page clients can actually trust, and you'll be part of what keeps that trust real behind the scenes.

To get started, click below to set up your account.

<x-mail::button :url="$acceptUrl">
Set up your account
</x-mail::button>

This link is personal to you and expires in {{ $expiresHours }} hours, so don't forward it along. If it expires before you get to it, just reach out and we'll send a fresh one.

On the next screen you'll add your name, set a password, and you're ready to go.

Welcome to the team — glad to have you.

— The {{ $appName }} Team

<x-slot:subcopy>
If the button doesn't open, paste this link in your browser: [{{ $acceptUrl }}]({{ $acceptUrl }})
</x-slot:subcopy>
</x-mail::message>
