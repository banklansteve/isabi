<x-mail::message>
**Isabi**

# {{ $subjectLine }}

{!! \Illuminate\Support\Str::markdown($bodyText) !!}

Thanks for being with us,<br>
**The Isabi team**

<x-slot:subcopy>
You're receiving this because you're part of Isabi. If this doesn't look right, reply to this email and we'll help.
</x-slot:subcopy>
</x-mail::message>
