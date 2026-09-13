<x-mail::message>
**Kraftrack**

# {{ $subjectLine }}

{!! \Illuminate\Support\Str::markdown($bodyText) !!}

Thanks for being with us,<br>
**The Kraftrack team**

<x-slot:subcopy>
You're receiving this because you're part of Kraftrack. If this doesn't look right, reply to this email and we'll help.
</x-slot:subcopy>
</x-mail::message>
