<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payslip — {{ $payslip->period_label }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #0B1F3A; font-size: 12px; margin: 0; padding: 32px; }
        .brand { font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
        .muted { color: #64748b; }
        .row { width: 100%; }
        .card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; padding: 6px 0; border-bottom: 1px solid #e2e8f0; }
        td { padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .amount { text-align: right; }
        .total { font-weight: 700; font-size: 14px; }
        .net { background: #E3ECFC; border-radius: 10px; padding: 14px 16px; margin-top: 16px; }
        .pill { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; background: #E3ECFC; color: #123B72; text-transform: uppercase; }
        h2 { font-size: 13px; margin: 0 0 8px; }
    </style>
</head>
<body>
    <table class="row">
        <tr>
            <td>
                <div class="brand">iSabi</div>
                <div class="muted">Payslip</div>
            </td>
            <td class="amount">
                <div class="pill">{{ ucfirst($payslip->status) }}</div>
                <div class="muted" style="margin-top:6px;">{{ $payslip->period_label }}</div>
            </td>
        </tr>
    </table>

    <div class="card">
        <table class="row">
            <tr>
                <td>
                    <h2>{{ $payslip->user->name }}</h2>
                    <div class="muted">{{ $payslip->user->email }}</div>
                </td>
                <td class="amount">
                    <div class="muted">Period</div>
                    <div>{{ $payslip->period_start->format('j M Y') }} – {{ $payslip->period_end->format('j M Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr><th>Earnings</th><th class="amount">Amount ({{ $payslip->currency }})</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Base pay</td>
                    <td class="amount">{{ number_format($payslip->base_pay, 2) }}</td>
                </tr>
                @foreach ($allowances as $item)
                    <tr>
                        <td>{{ $item->label }}</td>
                        <td class="amount">{{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Gross pay</td>
                    <td class="amount">{{ number_format($payslip->gross_pay, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if ($deductions->count())
        <div class="card">
            <table>
                <thead>
                    <tr><th>Deductions</th><th class="amount">Amount ({{ $payslip->currency }})</th></tr>
                </thead>
                <tbody>
                    @foreach ($deductions as $item)
                        <tr>
                            <td>{{ $item->label }}</td>
                            <td class="amount">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td>Total deductions</td>
                        <td class="amount">{{ number_format($payslip->deductions_total, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <table class="row net">
        <tr>
            <td class="total">Net pay</td>
            <td class="amount total">{{ $payslip->currency }} {{ number_format($payslip->net_pay, 2) }}</td>
        </tr>
    </table>

    @if ($payslip->notes)
        <div class="card">
            <h2>Notes</h2>
            <div class="muted">{{ $payslip->notes }}</div>
        </div>
    @endif

    <p class="muted" style="margin-top:24px; font-size:10px;">
        Generated {{ now()->format('j M Y') }} @if($payslip->generatedBy) by {{ $payslip->generatedBy->name }} @endif ·
        This is a record of pay for documentation purposes and does not itself constitute a payment.
    </p>
</body>
</html>
