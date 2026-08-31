<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $artisan->displayBusinessName() }} · Isabi</title>
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
        .head {
            background: linear-gradient(135deg, #1a4fb5 0%, #123b72 55%, #071427 100%);
            color: #fff;
            padding: 18px 18px 16px;
        }
        .brand-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            object-fit: cover;
            background: rgba(255,255,255,0.12);
            flex-shrink: 0;
        }
        .logo-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
        }
        .name {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .trade {
            font-size: 12px;
            opacity: 0.72;
            margin-top: 2px;
        }
        .stats {
            display: flex;
            gap: 8px;
            margin-top: 14px;
        }
        .stat {
            flex: 1;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 8px 10px;
        }
        .stat-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            opacity: 0.55;
        }
        .stat-value {
            font-size: 16px;
            font-weight: 800;
            margin-top: 2px;
        }
        .body { padding: 14px 16px 16px; }
        .section-title {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #5a6b82;
            margin-bottom: 10px;
        }
        .job {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            border-top: 1px solid rgba(11,31,58,0.06);
        }
        .job:first-of-type { border-top: 0; padding-top: 0; }
        .thumb {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            object-fit: cover;
            background: #eef3fb;
            flex-shrink: 0;
        }
        .job-title {
            font-size: 13px;
            font-weight: 700;
            line-height: 1.3;
        }
        .job-meta {
            font-size: 11px;
            color: #5a6b82;
            margin-top: 3px;
        }
        .ref {
            font-family: ui-monospace, monospace;
            font-size: 10px;
            color: #1a4fb5;
            font-weight: 700;
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
        <div class="head">
            <div class="brand-row">
                @if($artisan->brandLogoUrl())
                    <img src="{{ $artisan->brandLogoUrl() }}" alt="" class="logo">
                @else
                    <div class="logo logo-fallback">{{ strtoupper(substr($artisan->displayBusinessName(), 0, 1)) }}</div>
                @endif
                <div>
                    <div class="name">{{ $artisan->displayBusinessName() }}</div>
                    @if($artisan->trade)
                        <div class="trade">{{ $artisan->trade }}</div>
                    @endif
                </div>
            </div>
            <div class="stats">
                <div class="stat">
                    <div class="stat-label">Reviews</div>
                    <div class="stat-value">{{ $reviewCount }}</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Rating</div>
                    <div class="stat-value">{{ $avgRating ? number_format($avgRating, 1) : '—' }}</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Jobs</div>
                    <div class="stat-value">{{ $logs->count() }}+</div>
                </div>
            </div>
        </div>

        <div class="body">
            @if($reviews->isNotEmpty())
                <div class="section-title">Latest review</div>
                @php $review = $reviews->first(); @endphp
                <div class="review">
                    <div class="stars">{{ number_format((float) $review->rating, 1) }} / 5</div>
                    @if($review->comment)
                        <div class="quote">“{{ $review->comment }}”</div>
                    @endif
                </div>
            @endif

            @if($logs->isNotEmpty())
                <div class="section-title" style="margin-top:16px">Recent work</div>
                @foreach($logs->take(4) as $log)
                    <div class="job">
                        @php $thumb = $log->media->first(); @endphp
                        @if($thumb)
                            <img src="{{ $thumb->thumbUrl(200) }}" alt="" class="thumb">
                        @else
                            <div class="thumb"></div>
                        @endif
                        <div>
                            <div class="job-title">{{ $log->description }}</div>
                            <div class="job-meta">
                                @if($log->reference)
                                    <span class="ref">{{ $log->reference }}</span> ·
                                @endif
                                {{ $log->worked_on?->timezone(config('app.display_timezone'))->format('M Y') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="cta">View full page</a>
            <div class="foot">Powered by <a href="{{ url('/') }}" target="_blank" rel="noopener">Isabi</a></div>
        </div>
    </div>
</body>
</html>
