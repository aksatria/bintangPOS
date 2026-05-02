<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Stock Opname {{ $session->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 16px; margin: 0 0 6px; }
        .meta { margin-bottom: 10px; color: #334155; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; }
    </style>
</head>
<body>
    <h1>Stock Opname {{ $session->code }}</h1>
    <div class="meta">
        Status: {{ strtoupper($session->status) }} |
        Petugas: {{ $session->user?->name ?? '-' }} |
        Dibuat: {{ $session->created_at?->format('d/m/Y H:i') }}
    </div>
    @if(!empty($summary) && is_array($summary))
        <table style="margin-bottom: 10px;">
            <tr>
                <th style="width: 25%;">Item Naik</th>
                <th style="width: 25%;">Item Turun</th>
                <th style="width: 25%;">Item Tetap</th>
                <th style="width: 25%;">Net Selisih</th>
            </tr>
            <tr>
                <td>{{ number_format((int) ($summary['increased_count'] ?? 0), 0, ',', '.') }}</td>
                <td>{{ number_format((int) ($summary['decreased_count'] ?? 0), 0, ',', '.') }}</td>
                <td>{{ number_format((int) ($summary['equal_count'] ?? 0), 0, ',', '.') }}</td>
                <td>{{ number_format((int) ($summary['net_difference'] ?? 0), 0, ',', '.') }}</td>
            </tr>
        </table>

        @php($topDiffItems = $summary['top_diff_items'] ?? collect())
        @if($topDiffItems instanceof \Illuminate\Support\Collection && $topDiffItems->count() > 0)
            <table style="margin-bottom: 10px;">
                <tr>
                    <th colspan="3">Top 5 Selisih Terbesar</th>
                </tr>
                <tr>
                    <th>Produk</th>
                    <th>SKU</th>
                    <th>Selisih</th>
                </tr>
                @foreach($topDiffItems as $row)
                    <tr>
                        <td>{{ $row['name'] ?? '-' }}</td>
                        <td>{{ $row['sku'] ?? '-' }}</td>
                        <td>{{ number_format((int) ($row['difference'] ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    @endif

    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>SKU</th>
                <th>Stok Sistem</th>
                <th>Stok Fisik</th>
                <th>Selisih</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? '-' }}</td>
                    <td>{{ $item->product?->sku ?? '-' }}</td>
                    <td>{{ number_format((int) $item->system_stock, 0, ',', '.') }}</td>
                    <td>{{ $item->counted_stock !== null ? number_format((int) $item->counted_stock, 0, ',', '.') : '-' }}</td>
                    <td>{{ $item->difference !== null ? number_format((int) $item->difference, 0, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
