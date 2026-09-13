<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $log->description }} · {{ $artisan->displayBusinessName() }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f4f6fa;
            color: #0b1f3a;
            padding: 14px;
            line-height: 1.45;
        }
        .card {
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 18px 40px -24px rgba(11,31,58,0.35);
            border: 1px solid rgba(11,31,58,0.06);
        }
        .media {
            width: 100%;
            aspect-ratio: 16/10;
            object-fit: cover;
            background: #eef3fb;
            display: block;
        }
        .body { padding: 16px 18px 18px; }
        .eyebrow {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #1a4fb5;
        }
        .title {
            margin-top: 6px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.25;
        }
        .meta {
            margin-top: 8px;
            font-size: 12px;
            color: #5a6b82;
        }
        .ref {
            display: inline-block;
            margin-top: 10px;
            font-family: ui-monospace, monospace;
            font-size: 11px;
            font-weight: 700;
            color: #1a4fb5;
            background: #eef3fb;
            padding: 4px 8px;
            border-radius: 8px;
        }
        .review {
            margin-top: 14px;
            padding: 12px;
            background: #f5f8fe;
            border-radius: 14px;
            border-left: 3px solid #ff6a3d;
        }
        .stars { color: #c94c24; font-weight: 800; font-size: 12px; }
        .quote {
            margin-top: 6px;
            font-size: 13px;
            font-style: italic;
            color: #123b72;
        }
        .brand {
            margin-top: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-top: 14px;
            border-top: 1px solid rgba(11,31,58,0.06);
        }
        .logo {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            object-fit: cover;
            background: #eef3fb;
        }
        .brand-name { font-size: 13px; font-weight: 700; }
        .brand-trade { font-size: 11px; color: #5a6b82; }
        .cta {
            display: block;
            margin-top: 14px;
            text-align: center;
            background: #1a4fb5;
            color: #fff !important;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            padding: 12px 16px;
            border-radius: 14px;
        }
        .foot {
            margin-top: 10px;
            text-align: center;
            font-size: 10px;
            color: #8a95a8;
        }
        .foot a { color: #1a4fb5; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        @php $thumb = $log->media->first(); @endphp
        @if($thumb)
            <img src="{{ $thumb->previewUrl(900) }}" alt="" class="media">
        @endif
        <div class="body">
            @if($categoryLabel)
                <div class="eyebrow">{{ $categoryLabel }}</div>
            @endif
            <div class="title">{{ $log->description }}</div>
            <div class="meta">
                {{ $log->worked_on?->timezone(config('app.display_timezone'))->format('j M Y') }}
                · {{ $artisan->displayBusinessName() }}
            </div>
            @if($log->reference)
                <div class="ref">{{ $log->reference }}</div>
            @endif

            @if($log->review && $log->review->isPubliclyVisible())
                <div class="review">
                    <div class="stars">{{ number_format((float) $log->review->rating, 1) }} / 5</div>
                    @if($log->review->comment)
                        <div class="quote">“{{ $log->review->comment }}”</div>
                    @endif
                </div>
            @endif

            <div class="brand">
                @if($artisan->brandLogoUrl())
                    <img src="{{ $artisan->brandLogoUrl() }}" alt="" class="logo">
                @endif
                <div>
                    <div class="brand-name">{{ $artisan->displayBusinessName() }}</div>
                    @if($artisan->trade)
                        <div class="brand-trade">{{ $artisan->trade }}</div>
                    @endif
                </div>
            </div>

            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="cta">View job &amp; request quote</a>
            <div class="foot">Powered by <a href="{{ url('/') }}" target="_blank" rel="noopener">Kraftrack</a></div>
        </div>
    </div>
</body>
</html>
