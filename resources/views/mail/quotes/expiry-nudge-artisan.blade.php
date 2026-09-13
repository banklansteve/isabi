<x-mail::message>
@if($artisanName)
Hi {{ $artisanName }},
@else
Hi,
@endif

Your quote **{{ $quoteNumber }}** for **{{ $title }}** ({{ $clientName }})
@if($daysLeft === 1)
expires **tomorrow** ({{ $validUntil }}).
@else
expires in **{{ $daysLeft }} days** ({{ $validUntil }}).
@endif

**Total:** ₦{{ $totalNaira }}
@if($clientEmail)
**Client email:** {{ $clientEmail }}
@endif

They haven’t responded yet. You can follow up on WhatsApp, or revise the quote if needed.

<x-mail::button :url="$builderUrl">
Open in Kraftrack
</x-mail::button>

@if($quoteUrl)
Client link: [{{ $quoteUrl }}]({{ $quoteUrl }})
@endif

— The {{ $appName }} team

<x-slot:subcopy>
If the button doesn't open, paste this link in your browser: [{{ $builderUrl }}]({{ $builderUrl }})
</x-slot:subcopy>
</x-mail::message>
