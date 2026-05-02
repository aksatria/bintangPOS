<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $sale->invoice_number }}</title>
    <style>
        @page { size: {{ ($paper ?? '80') === '58' ? '58mm' : '80mm' }} auto; margin: 5mm 4mm 6mm 4mm; }
        html, body { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
        .wrap { width: 100%; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #444; }
        .line { border-top: 1px dashed #222; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .name { word-break: break-word; line-height: 1.25; }
        .nowrap { white-space: nowrap; }
        .tot { font-size: 12px; font-weight: 700; }
        .footer { margin-top: 6px; text-align: center; }
        .brand-logo { max-height: 34px; width: auto; max-width: 180px; margin: 0 auto 4px auto; display: block; object-fit: contain; }
        @media screen {
            body { background: #f1f5f9; }
            .wrap { max-width: 320px; margin: 12px auto; background: #fff; padding: 8px; box-shadow: 0 6px 20px rgba(0,0,0,.1); }
        }
    </style>
</head>
<body>
<div class="wrap">
    @php
        $qrisRef = null;
        $qrisIssuer = null;
        if (preg_match_all('/\[QRIS\]\s*Ref:\s*(.*?)\s*\|\s*Issuer:\s*(.*)/', (string) ($sale->note ?? ''), $qrisMatches, PREG_SET_ORDER) && count($qrisMatches) > 0) {
            $lastQris = $qrisMatches[count($qrisMatches) - 1];
            $qrisRef = trim((string) ($lastQris[1] ?? ''));
            $qrisIssuer = trim((string) ($lastQris[2] ?? ''));
        }
    @endphp
    <div class="center">
        @if(!empty($store?->logo) && \Illuminate\Support\Facades\Storage::disk('public')->exists((string) $store->logo))
            <img src="{{ asset('storage/' . $store->logo) }}" alt="{{ $store?->name ?? 'BINTANG' }}" class="brand-logo">
        @endif
        <strong>{{ $store?->name ?? 'BINTANG' }}</strong><br>
        <span class="muted">{{ $store?->address }}</span><br>
        <span class="muted">{{ $store?->whatsapp }}</span>
    </div>

    <div class="line"></div>

    <div>
        Invoice: {{ $sale->invoice_number }}<br>
        Tanggal: {{ $sale->sold_at?->format('d/m/Y H:i') }}<br>
        Kasir: {{ $sale->user?->name }}
    </div>

    <div class="line"></div>

    <table>
        @foreach ($sale->items as $item)
            <tr>
                <td class="name">
                    {{ $item->product_name }}<br>
                    <span class="muted">{{ $item->quantity }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                </td>
                <td class="right nowrap">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="line"></div>

    <table>
        <tr><td>Subtotal</td><td class="right nowrap">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</td></tr>
        <tr><td>Diskon</td><td class="right nowrap">Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Pajak</td><td class="right nowrap">Rp {{ number_format($sale->tax_amount, 0, ',', '.') }}</td></tr>
        <tr><td class="tot">Total</td><td class="right nowrap tot">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Status</td><td class="right">{{ $sale->status->value === 'paid' ? 'LUNAS' : ($sale->status->value === 'pending' ? 'PENDING' : 'BATAL') }}</td></tr>
        @if($sale->status->value === 'paid')
            <tr><td>Metode</td><td class="right">{{ strtoupper(str_replace('_', ' ', (string) ($sale->payment_method ?? 'cash'))) }}</td></tr>
            @if($qrisRef || $qrisIssuer)
                <tr><td>Ref QRIS</td><td class="right">{{ $qrisRef ?: '-' }}</td></tr>
                <tr><td>Issuer</td><td class="right">{{ $qrisIssuer ?: '-' }}</td></tr>
            @endif
            <tr><td>Dibayar</td><td class="right nowrap">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td></tr>
            <tr><td>Kembalian</td><td class="right nowrap">Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</td></tr>
            @if(is_array($sale->payment_breakdown) && count($sale->payment_breakdown) > 0)
                <tr><td colspan="2"><strong>Rincian Split</strong></td></tr>
                @foreach($sale->payment_breakdown as $row)
                    <tr>
                        <td>- {{ strtoupper(str_replace('_', ' ', (string) ($row['method'] ?? '-'))) }}</td>
                        <td class="right nowrap">Rp {{ number_format((float) ($row['amount'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        @else
            <tr><td>Metode</td><td class="right">BELUM DIBAYAR</td></tr>
            <tr><td>Dibayar</td><td class="right nowrap">Rp 0</td></tr>
            <tr><td>Kembalian</td><td class="right nowrap">Rp 0</td></tr>
        @endif
    </table>

    <div class="line"></div>
    <div class="footer">{{ $store?->receipt_footer }}</div>
</div>

<script>
window.addEventListener('load', () => {
    window.print();
    setTimeout(() => window.close(), 350);
});
</script>
</body>
</html>
