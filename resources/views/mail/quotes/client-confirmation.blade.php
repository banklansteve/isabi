<x-mail::message>
# Quote request received

@if($clientName)
Hi {{ $clientName }},
@else
Hi,
@endif

Thanks for reaching out to **{{ $businessName }}**@if($trade) ({{ $trade }})@endif. Your request is already in their inbox.

<x-mail::panel>
**Request summary**

@if($subject)
- **Subject:** {{ $subject }}
@endif
@if($jobLabel)
- **Referenced work:** {{ $jobLabel }}
@else
- **Source:** Their public {{ $appName }} page
@endif
@if($submittedAt)
- **Submitted:** {{ $submittedAt }}
@endif
</x-mail::panel>

@if($messageText)
**What you wrote**

{{ $messageText }}
@endif

**What happens next**

1. {{ $businessName }} reviews your details  
2. They follow up by phone, WhatsApp, or email  
3. You’ll receive a clear quote you can accept or decline  

Most artisans reply within one or two business days.

@if($profileUrl)
<x-mail::button :url="$profileUrl">
View {{ $businessName }} on {{ $appName }}
</x-mail::button>
@endif

If you didn’t submit this request, you can ignore this email.

Thanks,  
The {{ $appName }} team

<x-slot:subcopy>
This confirmation was sent because you requested a quote on {{ $appName }}. Replies go to {{ $businessName }}.
</x-slot:subcopy>
</x-mail::message>
