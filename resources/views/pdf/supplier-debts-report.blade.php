<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#0f172a; font-size:10px; line-height:1.35; }
        h1 { margin:0 0 4px; font-size:20px; }
        .muted { color:#64748b; }
        .summary { width:100%; margin:12px 0; border-collapse:collapse; }
        .summary td { border:1px solid #e2e8f0; padding:8px; }
        .summary span { display:block; color:#64748b; font-size:9px; text-transform:uppercase; }
        .summary strong { display:block; margin-top:3px; font-size:13px; }
        table { width:100%; border-collapse:collapse; }
        th, td { border-bottom:1px solid #e2e8f0; padding:6px; text-align:left; vertical-align:top; }
        th { background:#f8fafc; text-transform:uppercase; color:#334155; font-size:9px; }
        .num { text-align:right; white-space:nowrap; }
    </style>
</head>
<body>
    <h1>Laporan Hutang Supplier</h1>
    <div class="muted">Dicetak: {{ now()->format('d/m/Y H:i') }}</div>

    <table class="summary">
        <tr>
            <td><span>Total Hutang</span><strong>Rp {{ number_format((float) ($summary['total_remaining'] ?? 0), 0, ',', '.') }}</strong></td>
            <td><span>Overdue</span><strong>Rp {{ number_format((float) ($summary['total_overdue'] ?? 0), 0, ',', '.') }}</strong></td>
            <td><span>PO Terbuka</span><strong>{{ number_format((int) ($summary['open_count'] ?? 0), 0, ',', '.') }}</strong></td>
            <td><span>Supplier</span><strong>{{ number_format((int) ($summary['supplier_count'] ?? 0), 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            @foreach($agingSummary as $bucket)
                <td><span>{{ $bucket['label'] }}</span><strong>Rp {{ number_format((float) $bucket['amount'], 0, ',', '.') }}</strong><br>{{ number_format((int) $bucket['count'], 0, ',', '.') }} PO</td>
            @endforeach
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>PO</th>
                <th>Supplier</th>
                <th>Order</th>
                <th>Due</th>
                <th>Status</th>
                <th class="num">Total</th>
                <th class="num">Sisa</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->number }}</td>
                    <td>{{ $row->supplier?->name ?? '-' }}</td>
                    <td>{{ optional($row->ordered_at)->format('d/m/Y') ?: '-' }}</td>
                    <td>{{ optional($row->due_date)->format('d/m/Y') ?: '-' }}</td>
                    <td>{{ ($paymentStatusLabels ?? [])[(string) $row->payment_status] ?? ucfirst((string) $row->payment_status) }}</td>
                    <td class="num">Rp {{ number_format((float) $row->total_amount, 0, ',', '.') }}</td>
                    <td class="num">Rp {{ number_format((float) $row->remaining_amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
