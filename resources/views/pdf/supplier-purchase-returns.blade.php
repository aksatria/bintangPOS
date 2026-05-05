<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#0f172a; font-size:12px; line-height:1.45; }
        h1 { margin:0; font-size:22px; }
        .muted { color:#64748b; }
        .head { display:table; width:100%; margin-bottom:18px; }
        .head > div { display:table-cell; vertical-align:top; }
        .right { text-align:right; }
        .box { border:1px solid #dbe4f0; border-radius:8px; padding:10px; margin-bottom:12px; }
        table { width:100%; border-collapse:collapse; }
        th, td { border-bottom:1px solid #e2e8f0; padding:8px; text-align:left; vertical-align:top; }
        th { background:#f8fafc; color:#334155; font-size:11px; text-transform:uppercase; }
        .num { text-align:right; white-space:nowrap; }
    </style>
</head>
<body>
    <div class="head">
        <div>
            <h1>Nota Retur Supplier</h1>
            <div class="muted">{{ $purchase->number }}</div>
        </div>
        <div class="right">
            <strong>BINTANG</strong><br>
            <span class="muted">{{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    <div class="box">
        <strong>Supplier</strong><br>
        {{ $purchase->supplier?->name ?? '-' }}<br>
        <span class="muted">Invoice: {{ $purchase->supplier_invoice_number ?: '-' }} | Surat Jalan: {{ $purchase->delivery_note_number ?: '-' }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>Alasan</th>
                <th class="num">Qty</th>
                <th class="num">Harga</th>
                <th class="num">Potong Hutang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchase->returns as $return)
                <tr>
                    <td>{{ $return->item?->product_name ?? '-' }}<br><span class="muted">{{ optional($return->created_at)->format('d/m/Y H:i') }} oleh {{ $return->creator?->name ?? '-' }}</span></td>
                    <td>{{ $return->reason }}{{ $return->note ? ' - '.$return->note : '' }}</td>
                    <td class="num">{{ number_format((int) $return->quantity, 0, ',', '.') }}</td>
                    <td class="num">Rp {{ number_format((float) $return->unit_cost, 0, ',', '.') }}</td>
                    <td class="num">Rp {{ number_format((float) $return->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada retur.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="num">Total Potongan</th>
                <th class="num">Rp {{ number_format((float) $purchase->returns->sum('amount'), 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
