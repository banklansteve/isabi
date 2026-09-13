<x-mail::message>
# New quote request

@if($artisanName)
Hi {{ $artisanName }},
@else
Hi,
@endif

**{{ $clientName }}** just asked you for a quote on {{ $appName }}.

<x-mail::panel>
**Client details**

- **Name:** {{ $clientName }}
- **Phone:** {{ $clientPhone ?: '—' }}
- **Email:** {{ $clientEmail ?: '—' }}
@if($subject)
- **Subject:** {{ $subject }}
@endif
@if($jobLabel)
- **Referenced job:** {{ $jobLabel }}
@else
- **Source:** Your public page
@endif
</x-mail::panel>

@if($messageText)
**Their message**

{{ $messageText }}
@endif

Open the builder to call or WhatsApp them, then put your numbers together.

<x-mail::button :url="$viewUrl">
Open quote request
</x-mail::button>

<x-mail::button :url="$requestsUrl" color="secondary">
All quotes
</x-mail::button>

Thanks,  
The {{ $appName }} team

<x-slot:subcopy>
If the button doesn’t open, paste this link into your browser: [{{ $viewUrl }}]({{ $viewUrl }})
</x-slot:subcopy>
</x-mail::message>
