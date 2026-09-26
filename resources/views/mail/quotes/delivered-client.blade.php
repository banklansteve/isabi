<x-mail::message>
# Your quote from {{ $businessName }}

@if ($clientName)
Hi {{ $clientName }},
@else
Hi,
@endif

{{ $businessName }} sent you a quote for **{{ $title }}**.

- Quote: {{ $quoteNumber }}
- Total: NGN {{ $totalNaira }}
@if ($validUntil)
- Valid until: {{ $validUntil }}
@endif

<x-mail::button :url="$quoteUrl">
Open your quote
</x-mail::button>

You can also [download the PDF]({{ $pdfUrl }}).

@if ($hasPdfAttachment)
A copy of the PDF is attached to this email.
@endif

Reply to this email to reach {{ $businessName }} directly.

Thanks,
{{ $appName }}

<x-slot:subcopy>
If the button does not work, open: {{ $quoteUrl }}
</x-slot:subcopy>
</x-mail::message>
