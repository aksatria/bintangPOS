<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#0f172a; font-size:12px; line-height:1.45; }
        h1 { margin:0; font-size:22px; }
        .muted { color:#64748b; }
        .header { display:table; width:100%; margin-bottom:20px; }
        .header > div { display:table-cell; vertical-align:top; }
        .right { text-align:right; }
        .box { border:1px solid #dbe4f0; border-radius:8px; padding:10px; margin-bottom:12px; }
        table { width:100%; border-collapse:collapse; }
        th, td { border-bottom:1px solid #e2e8f0; padding:8px; text-align:left; vertical-align:top; }
        th { background:#f8fafc; color:#334155; font-size:11px; text-transform:uppercase; letter-spacing:.04em; }
        .num { text-align:right; white-space:nowrap; }
        .totals { width:45%; margin-left:auto; margin-top:12px; }
        .totals td { border-bottom:0; padding:4px 8px; }
        .grand { font-weight:bold; font-size:14px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Purchase Order</h1>
            <div class="muted">{{ $purchase->number }}</div>
        </div>
        <div class="right">
            <strong>BINTANG</strong><br>
            <span class="muted">Dokumen pembelian supplier</span>
        </div>
    </div>

    <div class="box">
        <table>
            <tr>
                <td><strong>Supplier</strong><br>{{ $purchase->supplier?->name ?? '-' }}<br><span class="muted">{{ $purchase->supplier?->code ?? '-' }}</span></td>
                <td><strong>Tanggal Order</strong><br>{{ optional($purchase->ordered_at)->format('d/m/Y') ?: '-' }}</td>
                <td><strong>Jatuh Tempo</strong><br>{{ optional($purchase->due_date)->format('d/m/Y') ?: '-' }}</td>
            </tr>
            <tr>
                <td><strong>No Invoice Supplier</strong><br>{{ $purchase->supplier_invoice_number ?: '-' }}</td>
                <td><strong>No Surat Jalan</strong><br>{{ $purchase->delivery_note_number ?: '-' }}</td>
                <td><strong>Status</strong><br>{{ ($purchaseStatusLabels ?? [])[(string) $purchase->status] ?? ucfirst((string) $purchase->status) }} / {{ ($paymentStatusLabels ?? [])[(string) $purchase->payment_status] ?? ucfirst((string) $purchase->payment_status) }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th class="num">Qty</th>
                <th class="num">Harga</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchase->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="num">{{ number_format((int) $item->quantity, 0, ',', '.') }}</td>
                    <td class="num">Rp {{ number_format((float) $item->unit_cost, 0, ',', '.') }}</td>
                    <td class="num">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num">Rp {{ number_format((float) $purchase->subtotal, 0, ',', '.') }}</td></tr>
        <tr><td>Diskon</td><td class="num">Rp {{ number_format((float) $purchase->discount_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Pajak</td><td class="num">Rp {{ number_format((float) $purchase->tax_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Ongkir</td><td class="num">Rp {{ number_format((float) $purchase->shipping_amount, 0, ',', '.') }}</td></tr>
        <tr class="grand"><td>Total</td><td class="num">Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Sudah Bayar</td><td class="num">Rp {{ number_format((float) $purchase->paid_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Sisa</td><td class="num">Rp {{ number_format((float) $purchase->remaining_amount, 0, ',', '.') }}</td></tr>
    </table>

    @if($purchase->note)
        <div class="box" style="margin-top:18px;">
            <strong>Catatan</strong><br>
            {{ $purchase->note }}
        </div>
    @endif
</body>
</html>
