<x-mail::message>
# Quote {{ $quoteNumber }} sent

@if ($artisanName)
Hi {{ $artisanName }},
@else
Hi,
@endif

Your quote for **{{ $title }}** was emailed to **{{ $clientName }}**.
@if ($clientEmail)
Client email: {{ $clientEmail }}
@endif

- Quote: {{ $quoteNumber }}
- Total: NGN {{ $totalNaira }}
@if ($validUntil)
- Valid until: {{ $validUntil }}
@endif

<x-mail::button :url="$builderUrl">
Open in {{ $appName }}
</x-mail::button>

Client link: {{ $quoteUrl }}

@if ($hasPdfAttachment)
A PDF copy is attached for your records. You can also [download it here]({{ $pdfUrl }}).
@else
[Download the PDF]({{ $pdfUrl }})
@endif

Thanks,
{{ $appName }}

<x-slot:subcopy>
Quote link: {{ $quoteUrl }}
</x-slot:subcopy>
</x-mail::message>
