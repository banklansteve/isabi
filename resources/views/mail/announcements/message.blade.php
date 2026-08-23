@component('mail::message')
# {{ $subjectLine }}

{!! nl2br(e($bodyText)) !!}

Thanks,<br>
The Isabi team
@endcomponent
