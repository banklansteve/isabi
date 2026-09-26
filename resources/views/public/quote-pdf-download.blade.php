<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Download quote PDF · {{ $appName }}</title>
    {{-- Start the file transfer after the HTML page loads (email clients often block direct PDF responses). --}}
    <meta http-equiv="refresh" content="1;url={{ $pdfUrl }}">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100dvh;
            font-family: "Plus Jakarta Sans", system-ui, -apple-system, sans-serif;
            background: #F5F8FE;
            color: #0B1F3A;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 28rem;
            background: #fff;
            border-radius: 1.25rem;
            padding: 1.75rem 1.5rem;
            box-shadow: 0 1px 2px rgba(11, 31, 58, 0.04), 0 8px 24px rgba(11, 31, 58, 0.06);
            border: 1px solid rgba(11, 31, 58, 0.06);
            text-align: center;
        }
        h1 {
            margin: 0;
            font-size: 1.35rem;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }
        p {
            margin: 0.75rem 0 0;
            font-size: 0.95rem;
            line-height: 1.5;
            color: rgba(11, 31, 58, 0.55);
            font-weight: 500;
        }
        .actions {
            margin-top: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }
        a.btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            border-radius: 1rem;
            padding: 0.9rem 1.1rem;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
        }
        a.primary {
            background: #1A4FB5;
            color: #fff;
            box-shadow: 0 12px 28px -12px rgba(26, 79, 181, 0.55);
        }
        a.primary:hover { background: #154296; }
        a.secondary {
            background: #fff;
            color: #1A4FB5;
            border: 1px solid rgba(26, 79, 181, 0.25);
        }
        .hint {
            margin-top: 1rem;
            font-size: 0.75rem;
            color: rgba(11, 31, 58, 0.35);
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Your quote PDF</h1>
        <p>
            @if($businessName)
                From {{ $businessName }}@if($quoteNumber) ({{ $quoteNumber }})@endif.
            @endif
            The download should start automatically.
        </p>
        <div class="actions">
            <a class="btn primary" id="pdf-link" href="{{ $pdfUrl }}">Download PDF</a>
            <a class="btn secondary" href="{{ $quoteUrl }}">View quote online</a>
        </div>
        <p class="hint">If nothing happens, tap Download PDF above.</p>
    </main>
    <script>
        (function () {
            var link = document.getElementById('pdf-link');
            if (!link) return;
            window.setTimeout(function () {
                window.location.href = link.href;
            }, 400);
        })();
    </script>
</body>
</html>
