@props(['url'])
@php
    /** @var \Illuminate\Mail\Message|null $message */
    $logoSrc = \App\Support\MailBrand::logoSrc(isset($message) ? $message : null);
    $appName = config('app.name', 'Kraftrack');
@endphp
<tr>
<td class="header" style="padding: 28px 0 18px; text-align: center;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none; border: 0; outline: none;" target="_blank" rel="noopener noreferrer">
<img
    src="{{ $logoSrc }}"
    width="56"
    height="56"
    alt="{{ $appName }}"
    title="{{ $appName }}"
    style="display: block; margin: 0 auto; width: 56px; height: 56px; border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic;"
/>
</a>
</td>
</tr>
