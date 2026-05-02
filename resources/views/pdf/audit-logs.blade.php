<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Audit Log Kasir</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h2 { margin: 0 0 4px; }
        .muted { color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
    </style>
</head>
<body>
    <h2>Audit Log Kasir</h2>
    <div class="muted">Periode: {{ $from }} s/d {{ $to }}</div>
    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Kasir</th>
                <th>Aksi</th>
                <th>Level</th>
                <th>Context</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? '-' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ strtoupper($severityOf($log->action)) }}</td>
                    <td>{{ json_encode($log->context ?? [], JSON_UNESCAPED_UNICODE) }}</td>
                    <td>{{ $log->ip_address ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

