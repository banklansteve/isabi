<x-mail::message>
Hi {{ $clientName }},

Thanks for reaching out to **{{ $businessName }}**@if($trade) ({{ $trade }})@endif — your quote request is in their inbox.

@if($jobLabel)
You asked about work similar to **{{ $jobLabel }}**.
@else
You sent your request through their public Isabi page.
@endif

@if($messageText)
**What you wrote**

{{ $messageText }}
@endif

**What happens next**

{{ $businessName }} will review your details and follow up by phone or email. Most artisans reply within one or two business days.

@if($profileUrl)
You can revisit their page any time:

<x-mail::button :url="$profileUrl">
View {{ $businessName }} on Isabi
</x-mail::button>
@endif

If you didn’t submit this request, you can ignore this email.

— The {{ $appName }} team

<x-slot:subcopy>
This confirmation was sent because you requested a quote on Isabi. Replies come directly from the artisan — not from {{ $appName }}.
</x-slot:subcopy>
</x-mail::message>
