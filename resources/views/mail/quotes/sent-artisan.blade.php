<x-mail::message>
# Your quote has been sent

@if($artisanName)
Hi {{ $artisanName }},
@else
Hi,
@endif

Good news — your quote **{{ $quoteNumber }}** for **{{ $title }}** has been emailed to **{{ $clientName }}**.

@if($clientEmail)
We sent it to **{{ $clientEmail }}** with the quote link, a PDF attachment, and accept / decline options.
@else
They don’t have an email on file — share the client link on WhatsApp so they can review it.
@endif

<x-mail::panel>
**Delivery summary**

- **Quote ID:** {{ $quoteNumber }}
- **Client:** {{ $clientName }}
@if($clientEmail)
- **Client email:** {{ $clientEmail }}
@endif
- **Total:** ₦{{ $totalNaira }}
@if($validUntil)
- **Valid until:** {{ $validUntil }}
@endif
</x-mail::panel>

A PDF copy is attached for your records. You can reshare the client link anytime.

<x-mail::button :url="$builderUrl">
Open in {{ $appName }}
</x-mail::button>

<x-mail::button :url="$quoteUrl" color="success">
View client quote
</x-mail::button>

<x-mail::button :url="$pdfUrl" color="secondary">
Download PDF
</x-mail::button>

Thanks,  
The {{ $appName }} team

<x-slot:subcopy>
**Client quote:** [{{ $quoteUrl }}]({{ $quoteUrl }})  
**PDF:** [{{ $pdfUrl }}]({{ $pdfUrl }})
</x-slot:subcopy>
</x-mail::message>
