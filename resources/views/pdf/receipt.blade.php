<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8px 8px 10px 8px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; margin: 0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .line { border-top: 1px dashed #222; margin: 6px 0; }
        .muted { color: #444; }
        .name { word-break: break-word; line-height: 1.25; }
        .nowrap { white-space: nowrap; }
        .mt2 { margin-top: 2px; }
        .tot { font-size: 11px; font-weight: 700; }
        .brand-logo { max-height: 34px; width: auto; max-width: 180px; margin: 0 auto 4px auto; display: block; object-fit: contain; }
    </style>
</head>
<body>
    @php
        $qrisRef = null;
        $qrisIssuer = null;
        if (preg_match_all('/\[QRIS\]\s*Ref:\s*(.*?)\s*\|\s*Issuer:\s*(.*)/', (string) ($sale->note ?? ''), $qrisMatches, PREG_SET_ORDER) && count($qrisMatches) > 0) {
            $lastQris = $qrisMatches[count($qrisMatches) - 1];
            $qrisRef = trim((string) ($lastQris[1] ?? ''));
            $qrisIssuer = trim((string) ($lastQris[2] ?? ''));
        }
    @endphp
    <div class="text-center">
        @if(!empty($store?->logo) && \Illuminate\Support\Facades\Storage::disk('public')->exists((string) $store->logo))
            <img src="{{ public_path('storage/' . $store->logo) }}" alt="{{ $store?->name ?? 'BINTANG' }}" class="brand-logo">
        @endif
        <strong>{{ $store?->name ?? 'BINTANG' }}</strong><br>
        <span class="muted">{{ $store?->address }}</span><br>
        <span class="muted">{{ $store?->whatsapp }}</span>
    </div>

    <div class="line"></div>

    <div>
        <span class="nowrap">Invoice: {{ $sale->invoice_number }}</span><br>
        <span class="nowrap">Tanggal: {{ $sale->sold_at?->format('d/m/Y H:i') }}</span><br>
        <span class="nowrap">Kasir: {{ $sale->user?->name }}</span>
    </div>

    <div class="line"></div>

    <table>
        @foreach ($sale->items as $item)
            <tr>
                <td class="name">
                    {{ $item->product_name }}<br>
                    <span class="muted">{{ $item->quantity }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                </td>
                <td class="text-right nowrap">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="line"></div>

    <table>
        <tr><td>Subtotal</td><td class="text-right nowrap">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</td></tr>
        <tr><td>Diskon</td><td class="text-right nowrap">Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Pajak</td><td class="text-right nowrap">Rp {{ number_format($sale->tax_amount, 0, ',', '.') }}</td></tr>
        <tr><td class="tot">Total</td><td class="text-right nowrap tot">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td></tr>
        <tr>
            <td>Status</td>
            <td class="text-right">
                {{ $sale->status->value === 'paid' ? 'LUNAS' : ($sale->status->value === 'pending' ? 'PENDING' : 'BATAL') }}
            </td>
        </tr>
        @if($sale->status->value === 'paid')
            <tr><td>Metode</td><td class="text-right">{{ strtoupper(str_replace('_', ' ', (string) ($sale->payment_method ?? 'cash'))) }}</td></tr>
            @if($qrisRef || $qrisIssuer)
                <tr><td>Ref QRIS</td><td class="text-right">{{ $qrisRef ?: '-' }}</td></tr>
                <tr><td>Issuer</td><td class="text-right">{{ $qrisIssuer ?: '-' }}</td></tr>
            @endif
            <tr><td>Dibayar</td><td class="text-right nowrap">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td></tr>
            <tr><td>Kembalian</td><td class="text-right nowrap">Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</td></tr>
            @if(is_array($sale->payment_breakdown) && count($sale->payment_breakdown) > 0)
                <tr><td colspan="2" class="mt2"><strong>Rincian Split:</strong></td></tr>
                @foreach($sale->payment_breakdown as $row)
                    <tr>
                        <td>- {{ strtoupper(str_replace('_', ' ', (string) ($row['method'] ?? '-'))) }}</td>
                        <td class="text-right nowrap">Rp {{ number_format((float) ($row['amount'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        @else
            <tr><td>Metode</td><td class="text-right">BELUM DIBAYAR</td></tr>
            <tr><td>Dibayar</td><td class="text-right nowrap">Rp 0</td></tr>
            <tr><td>Kembalian</td><td class="text-right nowrap">Rp 0</td></tr>
        @endif
    </table>

    <div class="line"></div>
    <div class="text-center">{{ $store?->receipt_footer }}</div>
</body>
</html>
