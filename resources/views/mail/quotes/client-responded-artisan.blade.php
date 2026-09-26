<x-mail::message>
# {{ $headline }}

@if ($artisanName)
Hi {{ $artisanName }},
@else
Hi,
@endif

{{ $summary }}

- Client: {{ $clientName }}
- Project: {{ $title }}
- Quote: {{ $quoteNumber }}

@if ($clientNote)
**Client note**

{{ $clientNote }}
@endif

<x-mail::button :url="$builderUrl">
{{ $ctaLabel }}
</x-mail::button>

Thanks,
{{ $appName }}

<x-slot:subcopy>
Open in {{ $appName }}: {{ $builderUrl }}
</x-slot:subcopy>
</x-mail::message>
