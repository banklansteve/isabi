<x-mail::message>
@if($clientName)
Hi {{ $clientName }},
@else
Hi,
@endif

Quick nudge — your quote from **{{ $businessName }}** for **{{ $title }}**
@if($daysLeft === 1)
expires **tomorrow** ({{ $validUntil }}).
@else
expires in **{{ $daysLeft }} days** ({{ $validUntil }}).
@endif

**Quote:** {{ $quoteNumber }}
**Total:** ₦{{ $totalNaira }}

Accept or decline before it lapses — no account needed.

<x-mail::button :url="$quoteUrl">
Review quote
</x-mail::button>

If you’re still deciding, open the link above and take a look when you have a moment.

— The {{ $appName }} team

<x-slot:subcopy>
If the button doesn't open, paste this link in your browser: [{{ $quoteUrl }}]({{ $quoteUrl }})
</x-slot:subcopy>
</x-mail::message>
