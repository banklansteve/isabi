<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Work log — {{ $user->displayBusinessName() }}</title>
    <style>
        @page { margin: 36px 40px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0B1F3A;
            font-size: 11px;
            line-height: 1.45;
        }
        .masthead {
            border-bottom: 2px solid #0B1F3A;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .brand {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #2F6FED;
            margin: 0 0 6px;
        }
        h1 {
            font-size: 22px;
            margin: 0 0 4px;
            line-height: 1.15;
        }
        .meta { color: #5a6b82; margin: 0; }
        .meta strong { color: #0B1F3A; }
        .stats {
            margin: 14px 0 20px;
            width: 100%;
            border-collapse: collapse;
        }
        .stats td {
            width: 33%;
            background: #F5F8FE;
            border: 1px solid #E3ECFC;
            padding: 10px 12px;
        }
        .stats .label {
            display: block;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #5a6b82;
            margin-bottom: 2px;
        }
        .stats .value {
            font-size: 16px;
            font-weight: 700;
        }
        .job {
            page-break-inside: avoid;
            border: 1px solid #E3ECFC;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .job-top {
            margin-bottom: 6px;
        }
        .eyebrow {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #2F6FED;
        }
        .job h2 {
            font-size: 13px;
            margin: 3px 0 4px;
        }
        .job .when { color: #5a6b82; font-size: 10px; }
        .review {
            margin-top: 8px;
            padding: 8px 10px;
            background: #F5F8FE;
            border-left: 3px solid #FF6A3D;
        }
        .stars { color: #C94C24; font-weight: 700; font-size: 10px; }
        .quote {
            margin: 4px 0 0;
            font-style: italic;
            color: #123B72;
        }
        .awaiting { color: #8a95a8; font-size: 10px; margin-top: 6px; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #8a95a8;
            border-top: 1px solid #E3ECFC;
            padding-top: 6px;
        }
        .footer .right { float: right; }
    </style>
</head>
<body>
    <div class="masthead">
        <p class="brand">Isabi · Proof of work</p>
        <h1>{{ $user->displayBusinessName() }}</h1>
        <p class="meta">
            @if($user->trade)<strong>{{ $user->trade }}</strong> · @endif
            @if($user->lga || $user->state)
                {{ collect([$user->lga, $user->state])->filter()->implode(', ') }} ·
            @endif
            Exported {{ $generatedAt->format('j M Y') }}
            @if($publicUrl)
                · {{ preg_replace('#^https?://#', '', $publicUrl) }}
            @endif
        </p>
    </div>

    @php
        $reviewed = $logs->filter(fn ($l) => $l->review)->count();
        $avg = $logs->filter(fn ($l) => $l->review)->avg(fn ($l) => (float) $l->review->rating);
    @endphp

    <table class="stats">
        <tr>
            <td>
                <span class="label">Jobs logged</span>
                <span class="value">{{ $logs->count() }}</span>
            </td>
            <td>
                <span class="label">Client reviews</span>
                <span class="value">{{ $reviewed }}</span>
            </td>
            <td>
                <span class="label">Average rating</span>
                <span class="value">{{ $avg ? number_format($avg, 1) : '—' }}</span>
            </td>
        </tr>
    </table>

    @forelse ($logs as $log)
        <div class="job">
            <div class="job-top">
                @if($log->job_category || $log->job_subcategory)
                    <div class="eyebrow">
                        {{ \App\Support\JobCategories::displayLabel($log->job_category, $log->job_subcategory) }}
                    </div>
                @endif
                <h2>{{ $log->description }}</h2>
                <div class="when">
                    {{ $log->worked_on?->timezone(config('app.display_timezone'))->format('j M Y') }}
                    @if($log->client_name) · {{ $log->client_name }} @endif
                    @php
                        $place = collect([$log->service_city, $log->service_lga, $log->service_state])->filter()->implode(', ');
                    @endphp
                    @if($place) · {{ $place }} @endif
                </div>
            </div>

            @if($log->review)
                <div class="review">
                    <div class="stars">
                        {{ number_format((float) $log->review->rating, 1) }} / 5
                        @if($log->review->would_recommend === true)
                            · Recommends
                        @endif
                    </div>
                    @if($log->review->comment)
                        <p class="quote">“{{ $log->review->comment }}”</p>
                    @endif
                    <div class="when" style="margin-top:4px">
                        {{ $log->review->client_display_name ?: 'Verified client' }}
                        @if($log->review->submitted_at)
                            · {{ $log->review->submitted_at->timezone(config('app.display_timezone'))->format('j M Y') }}
                        @endif
                    </div>
                </div>
            @else
                <p class="awaiting">No client review on this job yet.</p>
            @endif
        </div>
    @empty
        <p class="meta">No jobs logged yet.</p>
    @endforelse

    <div class="footer">
        <span>Generated from Isabi · Reviews shown as submitted by clients</span>
        <span class="right">{{ $user->displayBusinessName() }}</span>
    </div>
</body>
</html>
