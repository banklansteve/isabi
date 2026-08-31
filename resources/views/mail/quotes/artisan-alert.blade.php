<x-mail::message>
@if($artisanName)
Hi {{ $artisanName }},
@else
Hi,
@endif

**{{ $clientName }}** just asked you for a quote on Isabi.

@if($jobLabel)
**Referenced job:** {{ $jobLabel }}
@else
They found you through your public page.
@endif

**Contact details**

- **Phone:** {{ $clientPhone }}
- **Email:** {{ $clientEmail }}

@if($messageText)
**Their message**

{{ $messageText }}
@endif

Open the quote builder to call or email them, then put together your numbers.

<x-mail::button :url="$viewUrl">
View quote request
</x-mail::button>

You can also see all incoming requests on your quotes dashboard.

<x-mail::button :url="$requestsUrl" color="success">
All quote requests
</x-mail::button>

— The {{ $appName }} team

<x-slot:subcopy>
If the button doesn’t open, paste this link in your browser: [{{ $viewUrl }}]({{ $viewUrl }})
</x-slot:subcopy>
</x-mail::message>
