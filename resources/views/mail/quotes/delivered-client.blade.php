<x-mail::message>
@if($clientName)
Hi {{ $clientName }},
@else
Hi,
@endif

**{{ $businessName }}** has prepared a quote for **{{ $title }}**.

**Total:** ₦{{ $totalNaira }}
@if($validUntil)
**Valid until:** {{ $validUntil }}
@endif

Review the full breakdown, line items, and terms — then accept or decline in one tap. No account needed.

A PDF copy of this quote is attached to this email.

<x-mail::button :url="$quoteUrl">
View your quote
</x-mail::button>

<x-mail::button :url="$pdfUrl" color="success">
Download PDF
</x-mail::button>

If you have questions, reply directly to {{ $businessName }} on the phone or email they used when you first reached out.

— The {{ $appName }} team

<x-slot:subcopy>
If the button doesn't open, paste this link in your browser: [{{ $quoteUrl }}]({{ $quoteUrl }})
</x-slot:subcopy>
</x-mail::message>
