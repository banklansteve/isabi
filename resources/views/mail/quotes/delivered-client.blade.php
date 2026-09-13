<x-mail::message>
# Your quote is ready

@if($clientName)
Hi {{ $clientName }},
@else
Hi,
@endif

**{{ $businessName }}** has prepared an official quote for **{{ $title }}**.

Review the breakdown online, download the PDF, then accept or decline — no account needed.

<x-mail::panel>
**Quote details**

- **Quote ID:** {{ $quoteNumber }}
- **Total:** ₦{{ $totalNaira }}
@if($validUntil)
- **Valid until:** {{ $validUntil }}
@endif
@if($estimatedStart)
- **Estimated start:** {{ $estimatedStart }}
@endif
</x-mail::panel>

A PDF copy is attached to this email.

<x-mail::button :url="$quoteUrl">
View quote
</x-mail::button>

<x-mail::button :url="$pdfUrl" color="success">
Download PDF
</x-mail::button>

Questions? Reply to this email to reach **{{ $businessName }}** directly.

Thanks,  
The {{ $appName }} team

<x-slot:subcopy>
**Quote:** [{{ $quoteUrl }}]({{ $quoteUrl }})  
**PDF:** [{{ $pdfUrl }}]({{ $pdfUrl }})
</x-slot:subcopy>
</x-mail::message>
