<x-mail::message>
# New contact message

Someone reached out via the {{ config('app.name') }} contact form.

<x-mail::panel>
**From**  
{{ $payload['name'] }}  
{{ $payload['email'] }}  
@if(!empty($payload['phone']))
{{ $payload['phone'] }}  
@endif

**Topic:** {{ $payload['topic'] }}
</x-mail::panel>

**Message**

{{ $payload['message'] }}

Thanks,  
{{ config('app.name') }}
</x-mail::message>
