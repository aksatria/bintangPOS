<x-app-layout>
    <x-slot name="header">
        <div class="sdr-head">
            <div>
                <p class="sdr-kicker">Laporan Hutang</p>
                <h2 class="sdr-title">Hutang Supplier & Aging</h2>
                <p class="sdr-sub">Pantau sisa hutang, jatuh tempo, dan umur overdue dalam satu halaman.</p>
            </div>
            <div class="sdr-actions">
                <a class="sdr-btn" href="{{ route('admin.suppliers.index') }}">Kembali</a>
                <a class="sdr-btn" href="{{ route('admin.supplier-debts.export.excel', request()->query()) }}">Export Excel</a>
                <a class="sdr-btn sdr-btn-primary" href="{{ route('admin.supplier-debts.export.pdf', request()->query()) }}">Export PDF</a>
            </div>
        </div>
    </x-slot>

    <style>
        .sdr-shell { max-width:1280px; margin:0 auto; display:grid; gap:1rem; }
        .sdr-head { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; }
        .sdr-kicker { margin:0; color:#64748b; font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; font-weight:700; }
        .sdr-title { margin:.2rem 0 0; color:#0f172a; font-size:1.9rem; line-height:1.1; font-weight:800; }
        .sdr-sub { margin:.35rem 0 0; color:#64748b; font-size:.9rem; }
        .sdr-actions { display:flex; flex-wrap:wrap; gap:.5rem; justify-content:flex-end; }
        .sdr-btn { min-height:2.42rem; padding:0 .9rem; border:1px solid #cbd5e1; border-radius:10px; background:#fff; color:#334155; display:inline-flex; align-items:center; justify-content:center; font-size:.82rem; font-weight:700; }
        .sdr-btn-primary { background:#1d4ed8; border-color:#1d4ed8; color:#fff; }
        .sdr-card { background:#fff; border:1px solid #dbe4f0; border-radius:14px; box-shadow:0 10px 22px rgba(15,23,42,.04); overflow:hidden; }
        .sdr-card-head { padding:.9rem 1rem; border-bottom:1px solid #e7eef7; background:#f8fafc; }
        .sdr-card-title { margin:0; color:#334155; font-size:.82rem; text-transform:uppercase; letter-spacing:.08em; font-weight:750; }
        .sdr-card-body { padding:1rem; }
        .sdr-filter { display:grid; gap:.6rem; grid-template-columns:1fr; }
        @media (min-width:1080px) { .sdr-filter { grid-template-columns:minmax(190px,1fr) 180px 150px 150px 140px 140px auto; } }
        .sdr-input, .sdr-select { min-height:2.5rem; width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:0 .72rem; color:#0f172a; background:#fff; font-size:.88rem; }
        .sdr-summary { display:grid; gap:.75rem; grid-template-columns:repeat(4,minmax(0,1fr)); }
        .sdr-metric { border:1px solid #e2e8f0; border-radius:12px; padding:.8rem; background:#fff; }
        .sdr-metric span { display:block; color:#64748b; font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; font-weight:700; }
        .sdr-metric strong { display:block; margin-top:.24rem; color:#0f172a; font-size:1.08rem; font-weight:800; }
        .sdr-aging { display:grid; gap:.6rem; grid-template-columns:repeat(5,minmax(0,1fr)); }
        .sdr-aging-card { border:1px solid #e2e8f0; border-radius:12px; padding:.72rem; background:#f8fafc; }
        .sdr-aging-card span { display:block; color:#64748b; font-size:.72rem; font-weight:700; }
        .sdr-aging-card strong { display:block; margin-top:.22rem; color:#0f172a; }
        .sdr-table-wrap { border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
        .sdr-table { width:100%; border-collapse:collapse; }
        .sdr-table th, .sdr-table td { padding:.72rem; border-bottom:1px solid #e2e8f0; text-align:left; vertical-align:top; font-size:.82rem; }
        .sdr-table th { background:#f8fafc; color:#475569; text-transform:uppercase; letter-spacing:.05em; font-size:.7rem; font-weight:800; }
        .sdr-table tr:last-child td { border-bottom:0; }
        .sdr-title-cell { font-weight:700; color:#0f172a; }
        .sdr-muted { color:#64748b; margin-top:.15rem; }
        .sdr-money { white-space:nowrap; font-weight:800; color:#be123c; }
        .sdr-chip { display:inline-flex; border:1px solid #fecdd3; background:#fff1f2; color:#be123c; border-radius:999px; padding:.2rem .55rem; font-size:.68rem; font-weight:800; }
        .sdr-chip-ok { border-color:#a7f3d0; background:#ecfdf5; color:#047857; }
        @media (max-width:900px) { .sdr-head { flex-direction:column; align-items:flex-start; } .sdr-summary, .sdr-aging { grid-template-columns:1fr 1fr; } }
        @media (max-width:560px) { .sdr-summary, .sdr-aging { grid-template-columns:1fr; } }
    </style>

    <div class="sdr-shell">
        <div class="sdr-card">
            <div class="sdr-card-body">
                <form class="sdr-filter" method="GET" action="{{ route('admin.supplier-debts.index') }}">
                    <input class="sdr-input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari PO, invoice, surat jalan, supplier">
                    <select class="sdr-select" name="supplier_id">
                        <option value="0">Semua Supplier</option>
                        @foreach($supplierOptions as $supplier)
                            <option value="{{ $supplier->id }}" @selected((int) ($filters['supplier_id'] ?? 0) === (int) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    <select class="sdr-select" name="payment_status">
                        <option value="all" @selected(($filters['payment_status'] ?? 'all') === 'all')>Semua Status</option>
                        <option value="unpaid" @selected(($filters['payment_status'] ?? '') === 'unpaid')>Belum Bayar</option>
                        <option value="partial" @selected(($filters['payment_status'] ?? '') === 'partial')>Dibayar Sebagian</option>
                        <option value="overdue" @selected(($filters['payment_status'] ?? '') === 'overdue')>Lewat Tempo</option>
                    </select>
                    <select class="sdr-select" name="aging_bucket">
                        <option value="all" @selected(($filters['aging_bucket'] ?? 'all') === 'all')>Semua Aging</option>
                        <option value="not_due" @selected(($filters['aging_bucket'] ?? '') === 'not_due')>Belum Jatuh Tempo</option>
                        <option value="1_7" @selected(($filters['aging_bucket'] ?? '') === '1_7')>1-7 Hari</option>
                        <option value="8_14" @selected(($filters['aging_bucket'] ?? '') === '8_14')>8-14 Hari</option>
                        <option value="15_30" @selected(($filters['aging_bucket'] ?? '') === '15_30')>15-30 Hari</option>
                        <option value="over_30" @selected(($filters['aging_bucket'] ?? '') === 'over_30')>>30 Hari</option>
                    </select>
                    <input class="sdr-input" type="date" name="due_from" value="{{ $filters['due_from'] ?? '' }}">
                    <input class="sdr-input" type="date" name="due_to" value="{{ $filters['due_to'] ?? '' }}">
                    <button class="sdr-btn sdr-btn-primary" type="submit">Filter</button>
                </form>
            </div>
        </div>

        <div class="sdr-summary">
            <div class="sdr-metric"><span>Total Hutang</span><strong>Rp {{ number_format((float) ($summary['total_remaining'] ?? 0), 0, ',', '.') }}</strong></div>
            <div class="sdr-metric"><span>Overdue</span><strong>Rp {{ number_format((float) ($summary['total_overdue'] ?? 0), 0, ',', '.') }}</strong></div>
            <div class="sdr-metric"><span>PO Terbuka</span><strong>{{ number_format((int) ($summary['open_count'] ?? 0), 0, ',', '.') }}</strong></div>
            <div class="sdr-metric"><span>Supplier</span><strong>{{ number_format((int) ($summary['supplier_count'] ?? 0), 0, ',', '.') }}</strong></div>
        </div>

        <div class="sdr-aging">
            @foreach($agingSummary as $bucketKey => $bucket)
                <a class="sdr-aging-card" href="{{ route('admin.supplier-debts.index', array_merge(request()->query(), ['aging_bucket' => $bucketKey])) }}">
                    <span>{{ $bucket['label'] }}</span>
                    <strong>Rp {{ number_format((float) $bucket['amount'], 0, ',', '.') }}</strong>
                    <div class="sdr-muted">{{ number_format((int) $bucket['count'], 0, ',', '.') }} PO</div>
                </a>
            @endforeach
        </div>

        <div class="sdr-card">
            <div class="sdr-card-head"><h3 class="sdr-card-title">Daftar Hutang Supplier</h3></div>
            <div class="sdr-card-body">
                <div class="sdr-table-wrap">
                    <table class="sdr-table">
                        <thead><tr><th>PO & Supplier</th><th>Jadwal</th><th>Status</th><th>Nilai</th><th>Dokumen</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @forelse($rows as $row)
                                @php
                                    $isOverdue = $row->due_date && $row->due_date->lt(now()->startOfDay());
                                    $agingDays = $isOverdue ? $row->due_date->diffInDays(now()->startOfDay()) : 0;
                                @endphp
                                <tr>
                                    <td><div class="sdr-title-cell">{{ $row->number }}</div><div class="sdr-muted">{{ $row->supplier?->name ?? '-' }} · {{ $row->supplier?->code ?? '-' }}</div></td>
                                    <td>Order: {{ optional($row->ordered_at)->format('d/m/Y') ?: '-' }}<div class="sdr-muted">Due: {{ optional($row->due_date)->format('d/m/Y') ?: '-' }}</div></td>
                                    <td><span class="sdr-chip {{ $isOverdue ? '' : 'sdr-chip-ok' }}">{{ $isOverdue ? 'Overdue '.$agingDays.' hari' : 'Belum jatuh tempo' }}</span><div class="sdr-muted">{{ ($paymentStatusLabels ?? [])[(string) $row->payment_status] ?? ucfirst((string) $row->payment_status) }}</div></td>
                                    <td><div>Total: Rp {{ number_format((float) $row->total_amount, 0, ',', '.') }}</div><div class="sdr-money">Sisa: Rp {{ number_format((float) $row->remaining_amount, 0, ',', '.') }}</div></td>
                                    <td>{{ number_format((int) ($row->attachments_count ?? 0), 0, ',', '.') }} lampiran<div class="sdr-muted">{{ number_format((int) ($row->items_count ?? 0), 0, ',', '.') }} item</div></td>
                                    <td><a class="sdr-btn" href="{{ route('admin.supplier-purchases.show', $row) }}">Detail</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="sdr-muted">Tidak ada hutang supplier sesuai filter.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($rows->hasPages())
                    <div class="mt-4">{{ $rows->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
