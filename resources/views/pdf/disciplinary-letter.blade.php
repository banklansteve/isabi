<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $action->letter_subject }} — {{ $case->reference }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #0B1F3A; font-size: 12.5px; line-height: 1.55; margin: 0; padding: 48px 52px; }
        .letterhead { border-bottom: 2px solid #1A4FB5; padding-bottom: 14px; margin-bottom: 22px; }
        .brand { font-size: 20px; font-weight: 700; letter-spacing: -0.4px; }
        .eyebrow { color: #64748b; font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; margin-top: 4px; }
        .meta { width: 100%; margin-bottom: 22px; }
        .meta td { vertical-align: top; padding: 0 0 4px; }
        .muted { color: #64748b; font-size: 11px; }
        .label { color: #64748b; font-size: 9px; letter-spacing: 0.12em; text-transform: uppercase; }
        .body { white-space: pre-wrap; }
        .footer { margin-top: 36px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 9.5px; }
        .confidential { font-size: 10px; letter-spacing: 0.08em; text-transform: uppercase; color: #123B72; }
    </style>
</head>
<body>
    <div class="letterhead">
        <div class="brand">{{ config('app.name', 'Kraftrack') }}</div>
        <div class="eyebrow">Employment notice · Private and confidential</div>
    </div>

    <table class="meta">
        <tr>
            <td>
                <div class="label">To</div>
                <div>{{ $case->staff?->name }}</div>
                <div class="muted">{{ $case->staff?->email }}</div>
            </td>
            <td style="text-align:right;">
                <div class="label">Case reference</div>
                <div>{{ $case->reference }}</div>
                <div class="muted">Issued {{ $action->issued_at?->format('j F Y') }}</div>
            </td>
        </tr>
    </table>

    <p class="confidential">{{ $action->typeLabel() }}</p>

    <div class="body">{{ $action->letter_body }}</div>

    <div class="footer">
        This document forms part of the employment record for the named staff member.
        It is issued for formal record-keeping and acknowledgment. It is not a public document.
        @if($action->issuer)
            Recorded by {{ $action->issuer->name }}.
        @endif
    </div>
</body>
</html>
