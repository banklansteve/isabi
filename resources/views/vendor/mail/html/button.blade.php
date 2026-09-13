@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])

@php
    $palette = match ($color) {
        'success', 'green' => ['bg' => '#0F7A4A', 'border' => '#0F7A4A'],
        'secondary', 'gray' => ['bg' => '#334155', 'border' => '#334155'],
        default => ['bg' => '#1A4FB5', 'border' => '#1A4FB5'],
    };
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center" bgcolor="{{ $palette['bg'] }}" style="border-radius: 12px; background-color: {{ $palette['bg'] }};">
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener" style="background-color: {{ $palette['bg'] }}; border-bottom: 10px solid {{ $palette['border'] }}; border-left: 22px solid {{ $palette['border'] }}; border-right: 22px solid {{ $palette['border'] }}; border-top: 10px solid {{ $palette['border'] }}; border-radius: 12px; color: #ffffff !important; display: inline-block; font-size: 15px; font-weight: 700; line-height: 1.2; text-decoration: none;">
{!! $slot !!}
</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
