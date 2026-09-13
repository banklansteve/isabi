@props(['url'])
<tr>
<td class="header" style="padding: 28px 0 12px;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<span style="display: inline-block; font-size: 20px; font-weight: 800; letter-spacing: -0.02em; color: #1A4FB5; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;">
{{ trim($slot) ?: config('app.name') }}
</span>
</a>
</td>
</tr>
