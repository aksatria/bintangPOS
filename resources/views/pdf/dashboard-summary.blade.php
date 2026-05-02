<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Dashboard Snapshot</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        h1 { margin: 0 0 4px; font-size: 20px; }
        .muted { color: #64748b; margin-bottom: 14px; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .grid th, .grid td { border: 1px solid #cbd5e1; padding: 8px; vertical-align: top; }
        .grid th { background: #f1f5f9; text-align: left; }
    </style>
</head>
<body>
    <h1>Dashboard Snapshot</h1>
    <div class="muted">Periode: {{ $periodText }}</div>

    <table class="grid">
        <tr>
            <th>Omzet Hari Ini</th><td>Rp {{ number_format($omzetToday, 0, ',', '.') }}</td>
            <th>Transaksi Hari Ini</th><td>{{ number_format($transactionsToday, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Stok Habis</th><td>{{ number_format($outOfStockCount, 0, ',', '.') }}</td>
            <th>Nilai Aset Stok</th><td>Rp {{ number_format($inventoryAssetValue, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="grid">
        <tr><th colspan="2">Ringkasan Profit Periode</th></tr>
        <tr><th>Omzet Periode</th><td>Rp {{ number_format($omzetRange, 0, ',', '.') }}</td></tr>
        <tr><th>Total HPP</th><td>Rp {{ number_format($hppRange, 0, ',', '.') }}</td></tr>
        <tr><th>Pengeluaran</th><td>Rp {{ number_format($expensesRange, 0, ',', '.') }}</td></tr>
        <tr><th>Laba Kotor</th><td>Rp {{ number_format($grossProfitRange, 0, ',', '.') }}</td></tr>
        <tr><th>Laba Bersih</th><td>Rp {{ number_format($netProfitRange, 0, ',', '.') }}</td></tr>
        <tr><th>Transaksi Paid</th><td>{{ number_format($transactionsRange, 0, ',', '.') }}</td></tr>
    </table>
</body>
</html>
