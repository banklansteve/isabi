@component('mail::message')
# You're invited to Isabi operations

Hi{{ $invitee->first_name ? ' '.$invitee->first_name : '' }},

You've been invited to the Isabi admin team@if($invitedBy) by **{{ $invitedBy->name }}**@endif. Use the button below to set up your account.

This invite **expires in {{ $expiresHours }} hours**. After that you’ll need a Super Admin to send a new one.

@component('mail::button', ['url' => $acceptUrl])
Set up your account
@endcomponent

If you weren’t expecting this, ignore the email — nobody can reach the admin area until you finish setup.

Thanks,<br>
The Isabi team
@endcomponent
