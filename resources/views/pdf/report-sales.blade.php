<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f4f6; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>Laporan Penjualan</h2>
    <p>Periode: {{ strtoupper($period) }} ({{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }})</p>
    @php($meta = (array) ($exportMeta ?? []))
    <p>
        Jejak Export:
        {{ $meta['exported_at'] ?? '-' }} |
        Oleh: {{ $meta['exported_by'] ?? '-' }} |
        Alasan: {{ $meta['export_reason'] ?: '-' }} |
        Approval: {{ $meta['approved_by'] ?: '-' }} |
        Jumlah: {{ number_format((int) ($meta['selected_count'] ?? 0), 0, ',', '.') }} |
        Nilai: Rp {{ number_format((float) ($meta['selected_total'] ?? 0), 0, ',', '.') }}
    </p>

    <p>
        Omzet: Rp {{ number_format($omzet, 0, ',', '.') }} |
        Modal: Rp {{ number_format($modal, 0, ',', '.') }} |
        Pengeluaran: Rp {{ number_format($expenses, 0, ',', '.') }} |
        Laba: Rp {{ number_format($profit, 0, ',', '.') }}
    </p>
    @if(isset($mom))
        <p>
            MoM Omzet: {{ is_null(data_get($mom, 'omzet_pct')) ? '-' : number_format((float) data_get($mom, 'omzet_pct', 0), 2, ',', '.').'%' }} |
            MoM Laba: {{ is_null(data_get($mom, 'profit_pct')) ? '-' : number_format((float) data_get($mom, 'profit_pct', 0), 2, ',', '.').'%' }}
        </p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Invoice</th>
                <th>Kasir</th>
                <th>Pelanggan</th>
                <th>Status</th>
                <th>Metode</th>
                <th>Ref QRIS</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sales as $sale)
                <tr>
                    <td>{{ $sale->sold_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $sale->invoice_number }}</td>
                    <td>{{ $sale->user?->name }}</td>
                    <td>{{ $sale->customer?->name ?: ($sale->customer_name ?: '-') }}</td>
                    <td>{{ strtoupper($sale->status->value) }}</td>
                    <td>{{ strtoupper(str_replace('_', ' ', (string) ($sale->payment_method ?? 'cash'))) }}</td>
                    <td>{{ $sale->qris_reference_id ?: '-' }}</td>
                    <td class="text-right">{{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Metode Pembayaran</th>
                <th class="text-right">Jumlah Transaksi</th>
                <th class="text-right">Nilai Omzet</th>
                <th class="text-right">Kontribusi</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($paymentSummary ?? []) as $row)
                <tr>
                    <td>{{ strtoupper(str_replace('_', ' ', (string) ($row['method'] ?? '-'))) }}</td>
                    <td class="text-right">{{ number_format((int) ($row['count'] ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((float) ($row['amount'] ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((float) ($row['pct'] ?? 0), 1, ',', '.') }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Belum ada transaksi paid pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
