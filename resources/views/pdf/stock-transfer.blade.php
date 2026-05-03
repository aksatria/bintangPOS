<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Mutasi Stok</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .meta { margin-bottom: 12px; }
        .meta div { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
    <h1>Bukti Mutasi Stok Antar Cabang</h1>
    <div class="meta">
        <div><strong>Kode:</strong> {{ $transfer->code }}</div>
        <div><strong>Status:</strong> {{ strtoupper($transfer->status) }}</div>
        <div><strong>Cabang Asal:</strong> {{ $transfer->sourceBranch?->name }} ({{ $transfer->sourceBranch?->code }})</div>
        <div><strong>Cabang Tujuan:</strong> {{ $transfer->destinationBranch?->name }} ({{ $transfer->destinationBranch?->code }})</div>
        <div><strong>Requester:</strong> {{ $transfer->requester?->name ?: '-' }}</div>
        <div><strong>Approver:</strong> {{ $transfer->approver?->name ?: '-' }}</div>
        <div><strong>Receiver:</strong> {{ $transfer->receiver?->name ?: '-' }}</div>
        <div><strong>Ref Pengiriman:</strong> {{ $transfer->delivery_ref ?: '-' }}</div>
        <div><strong>Kurir/Pengantar:</strong> {{ $transfer->courier_name ?: '-' }}</div>
        <div><strong>Catatan Receive:</strong> {{ $transfer->receive_note ?: '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $transfer->created_at?->format('d/m/Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Produk</th>
                <th>Unit</th>
                <th>Qty Request</th>
                <th>Qty Receive</th>
                <th>Alasan Selisih</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transfer->items as $item)
                <tr>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->unit }}</td>
                    <td>{{ number_format((int) $item->requested_qty, 0, ',', '.') }}</td>
                    <td>{{ $item->received_qty !== null ? number_format((int) $item->received_qty, 0, ',', '.') : '-' }}</td>
                    <td>{{ $item->discrepancy_reason ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 28px;">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="width:50%; border:0; text-align:center;">
                    <div><strong>Pengirim (Cabang Asal)</strong></div>
                    <div style="height:72px;"></div>
                    <div>(__________________________)</div>
                    <div style="font-size:11px; color:#475569;">Nama & Tanda Tangan</div>
                </td>
                <td style="width:50%; border:0; text-align:center;">
                    <div><strong>Penerima (Cabang Tujuan)</strong></div>
                    <div style="height:72px;"></div>
                    <div>(__________________________)</div>
                    <div style="font-size:11px; color:#475569;">Nama & Tanda Tangan</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
