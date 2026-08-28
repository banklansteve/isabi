@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center" bgcolor="#1A4FB5" style="border-radius: 12px; background-color: #1A4FB5;">
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener" style="background-color: #1A4FB5; border: 8px solid #1A4FB5; border-radius: 12px; color: #ffffff !important; display: inline-block; font-size: 15px; font-weight: 700; line-height: 1.2; padding: 4px 10px; text-decoration: none;">
{!! $slot !!}
</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
