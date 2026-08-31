<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quote {{ $quote->quote_number }} — {{ $artisan->displayBusinessName() }}</title>
    <style>
        @page { margin: 36px 40px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0B1F3A;
            font-size: 10.5px;
            line-height: 1.45;
        }
        .masthead {
            border-bottom: 2px solid #1A4FB5;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .masthead-row {
            display: table;
            width: 100%;
        }
        .masthead-brand {
            display: table-cell;
            vertical-align: middle;
            width: 72px;
        }
        .masthead-logo {
            width: 58px;
            height: 58px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid #E3ECFC;
        }
        .masthead-copy {
            display: table-cell;
            vertical-align: middle;
            padding-left: 14px;
        }
        .brand {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #1A4FB5;
            margin: 0 0 4px;
        }
        h1 {
            font-size: 20px;
            margin: 0 0 2px;
            line-height: 1.15;
            font-weight: 700;
        }
        .meta { color: #5a6b82; margin: 0; font-size: 10px; }
        .meta strong { color: #0B1F3A; }
        .grid-two {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }
        .grid-two-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
        }
        .panel {
            background: #F5F8FE;
            border: 1px solid #E3ECFC;
            border-radius: 8px;
            padding: 12px 14px;
        }
        .panel-label {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #5a6b82;
            margin: 0 0 6px;
        }
        .panel p { margin: 0 0 3px; }
        .section-title {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #1A4FB5;
            margin: 0 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #E3ECFC;
        }
        .scope {
            margin: 0 0 18px;
            padding: 12px 14px;
            background: #FAFBFE;
            border: 1px solid #E3ECFC;
            border-radius: 8px;
            font-size: 10.5px;
            line-height: 1.5;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        table.items th {
            text-align: left;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #5a6b82;
            padding: 8px 10px;
            border-bottom: 2px solid #E3ECFC;
            background: #F5F8FE;
        }
        table.items td {
            padding: 9px 10px;
            border-bottom: 1px solid #EEF2F8;
            vertical-align: top;
        }
        table.items td.amount {
            text-align: right;
            font-weight: 700;
            white-space: nowrap;
        }
        .totals {
            width: 260px;
            margin-left: auto;
            margin-bottom: 18px;
        }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals td {
            padding: 5px 0;
            font-size: 10.5px;
        }
        .totals td.label { color: #5a6b82; }
        .totals td.value { text-align: right; font-weight: 700; }
        .totals tr.grand td {
            padding-top: 8px;
            border-top: 2px solid #1A4FB5;
            font-size: 14px;
            color: #1A4FB5;
        }
        .notes {
            margin-bottom: 14px;
            padding: 10px 12px;
            background: #FFFBF5;
            border: 1px solid #F5E6CC;
            border-radius: 8px;
            font-size: 10px;
        }
        .terms {
            font-size: 9.5px;
            color: #5a6b82;
            line-height: 1.5;
            margin-bottom: 8px;
        }
        .footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #E3ECFC;
            font-size: 8.5px;
            color: #8a97a8;
            text-align: center;
        }
        .quote-ref {
            float: right;
            text-align: right;
            font-size: 9px;
            color: #5a6b82;
        }
        .quote-ref strong { display: block; font-size: 11px; color: #0B1F3A; }
    </style>
</head>
<body>
    <div class="quote-ref">
        <span>Quote reference</span>
        <strong>{{ $quote->quote_number }}</strong>
        @if($quote->valid_until)
            <span>Valid until {{ $quote->valid_until->format('j M Y') }}</span>
        @endif
    </div>

    <div class="masthead">
        <div class="masthead-row">
            @if($brandLogo)
                <div class="masthead-brand">
                    <img src="{{ $brandLogo }}" alt="" class="masthead-logo">
                </div>
            @endif
            <div class="masthead-copy">
                <p class="brand">Quote</p>
                <h1>{{ $artisan->displayBusinessName() }}</h1>
                <p class="meta">
                    @if($artisan->trade)<strong>{{ $artisan->trade }}</strong> · @endif
                    @if($artisan->whatsapp){{ $artisan->whatsapp }}@endif
                    @if($artisan->email) · {{ $artisan->email }}@endif
                </p>
                @if($artisan->office_address)
                    <p class="meta">{{ $artisan->office_address }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid-two">
        <div class="grid-two-col">
            <div class="panel">
                <p class="panel-label">Prepared for</p>
                <p><strong>{{ $client['name'] }}</strong></p>
                @if($client['phone'])<p>{{ $client['phone'] }}</p>@endif
                @if($client['email'])<p>{{ $client['email'] }}</p>@endif
            </div>
        </div>
        <div class="grid-two-col">
            <div class="panel">
                <p class="panel-label">Project</p>
                <p><strong>{{ $request->displayTitle() }}</strong></p>
                @if($quote->estimated_start)
                    <p>Est. start: {{ $quote->estimated_start->format('j M Y') }}</p>
                @endif
                @if($quote->estimated_duration_days)
                    <p>Duration: {{ $quote->estimated_duration_days }} day{{ $quote->estimated_duration_days === 1 ? '' : 's' }}</p>
                @endif
            </div>
        </div>
    </div>

    @if($quote->scope_of_work)
        <p class="section-title">Scope of work</p>
        <div class="scope">{!! nl2br(e($quote->scope_of_work)) !!}</div>
    @endif

    <p class="section-title">Breakdown</p>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 52%">Item</th>
                <th style="width: 18%">Units</th>
                <th style="width: 30%">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lineItems as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>
                        @if(\App\Support\Quotes\QuoteLineItem::isMaterials($row['kind']) && $row['quantity'] > 0)
                            {{ rtrim(rtrim(number_format($row['quantity'], 2, '.', ''), '0'), '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="amount">{{ number_format(\App\Support\Quotes\QuoteLineItem::lineTotalNaira($row), 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">₦{{ number_format($totals['subtotal'], 0) }}</td>
            </tr>
            @if($totals['discount'] > 0)
                <tr>
                    <td class="label">Discount</td>
                    <td class="value">− ₦{{ number_format($totals['discount'], 0) }}</td>
                </tr>
            @endif
            @if($totals['vat'] > 0)
                <tr>
                    <td class="label">VAT ({{ rtrim(rtrim(number_format($totals['vat_rate'], 1, '.', ''), '0'), '.') }}%)</td>
                    <td class="value">₦{{ number_format($totals['vat'], 0) }}</td>
                </tr>
            @endif
            <tr class="grand">
                <td class="label"><strong>Total</strong></td>
                <td class="value"><strong>₦{{ number_format($totals['total'], 0) }}</strong></td>
            </tr>
        </table>
    </div>

    @if($quote->notes)
        <p class="section-title">Notes</p>
        <div class="notes">{!! nl2br(e($quote->notes)) !!}</div>
    @endif

    @if($quote->payment_terms)
        <p class="section-title">Payment terms</p>
        <p class="terms">{!! nl2br(e($quote->payment_terms)) !!}</p>
    @endif

    @if($quote->terms)
        <p class="section-title">Terms &amp; conditions</p>
        <p class="terms">{!! nl2br(e($quote->terms)) !!}</p>
    @endif

    <div class="footer">
        Generated {{ $generatedAt->format('j M Y · g:i A') }} · {{ config('app.name') }}
    </div>
</body>
</html>
