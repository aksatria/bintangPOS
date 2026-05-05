<x-app-layout>
    <x-slot name="header">
        <div class="sp-head">
            <div>
                <p class="sp-kicker">Detail Supplier</p>
                <h2 class="sp-title">{{ $supplier->name }}</h2>
                <p class="sp-sub">{{ $supplier->code }} - {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}</p>
            </div>
            <div class="sp-actions">
                <a class="sp-btn" href="{{ route('admin.suppliers.index') }}">Kembali</a>
                <a class="sp-btn sp-btn-primary" href="{{ route('admin.suppliers.index', ['purchase_supplier_id' => $supplier->id]) }}">Lihat di Pembelian</a>
            </div>
        </div>
    </x-slot>

    <style>
        .sp-shell { max-width:1120px; margin:0 auto; display:grid; gap:1rem; }
        .sp-head { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; }
        .sp-kicker { margin:0; font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; color:#64748b; font-weight:700; }
        .sp-title { margin:.2rem 0 0; font-size:1.85rem; line-height:1.1; color:#0f172a; font-weight:800; }
        .sp-sub { margin:.3rem 0 0; color:#64748b; font-size:.9rem; }
        .sp-actions { display:flex; flex-wrap:wrap; gap:.5rem; justify-content:flex-end; }
        .sp-btn { min-height:2.35rem; padding:0 .9rem; border:1px solid #cbd5e1; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; color:#334155; background:#fff; font-size:.82rem; font-weight:700; text-decoration:none; }
        .sp-btn-primary { color:#fff; background:#1d4ed8; border-color:#1d4ed8; }
        .sp-grid { display:grid; gap:1rem; grid-template-columns:minmax(0,1.22fr) minmax(340px,.78fr); align-items:start; }
        .sp-stack { display:grid; gap:1rem; }
        .sp-card { background:#fff; border:1px solid #dbe4f0; border-radius:14px; box-shadow:0 10px 22px rgba(15,23,42,.04); overflow:hidden; }
        .sp-card-head { padding:.9rem 1rem; border-bottom:1px solid #e7eef7; background:linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%); display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
        .sp-card-title { margin:0; font-size:.82rem; text-transform:uppercase; letter-spacing:.08em; color:#334155; font-weight:700; }
        .sp-card-body { padding:1rem; }
        .sp-kpis { display:grid; gap:.7rem; grid-template-columns:repeat(4,minmax(0,1fr)); }
        .sp-kpi, .sp-info { border:1px solid #e2e8f0; border-radius:12px; padding:.75rem; background:#fff; }
        .sp-kpi span, .sp-info span { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:#64748b; font-weight:700; }
        .sp-kpi strong, .sp-info strong { display:block; margin-top:.22rem; color:#0f172a; font-weight:750; overflow-wrap:anywhere; }
        .sp-kpi-blue strong { color:#1d4ed8; }
        .sp-kpi-red strong { color:#be123c; }
        .sp-kpi-amber strong { color:#c2410c; }
        .sp-info-grid { display:grid; gap:.7rem; grid-template-columns:repeat(2,minmax(0,1fr)); }
        .sp-list { display:grid; gap:.7rem; }
        .sp-row { border:1px solid #e2e8f0; border-radius:12px; padding:.78rem; background:#fff; display:grid; gap:.55rem; }
        .sp-row-head { display:flex; justify-content:space-between; gap:.75rem; align-items:flex-start; }
        .sp-row-title { color:#0f172a; font-weight:750; }
        .sp-row-sub { color:#64748b; font-size:.8rem; margin-top:.14rem; line-height:1.35; }
        .sp-money { text-align:right; white-space:nowrap; }
        .sp-chip { display:inline-flex; align-items:center; border:1px solid; border-radius:999px; padding:.2rem .52rem; font-size:.68rem; font-weight:800; white-space:nowrap; }
        .sp-chip-draft { color:#1d4ed8; border-color:#bfdbfe; background:#eff6ff; }
        .sp-chip-received, .sp-chip-paid, .sp-chip-active { color:#047857; border-color:#a7f3d0; background:#ecfdf5; }
        .sp-chip-partial_received, .sp-chip-partial { color:#c2410c; border-color:#fdba74; background:#fff7ed; }
        .sp-chip-unpaid, .sp-chip-overdue, .sp-chip-inactive { color:#be123c; border-color:#fecdd3; background:#fff1f2; }
        .sp-chip-cancelled { color:#475569; border-color:#cbd5e1; background:#f8fafc; }
        .sp-table { width:100%; border-collapse:collapse; }
        .sp-table td { padding:.62rem 0; border-bottom:1px solid #e2e8f0; font-size:.84rem; color:#475569; vertical-align:top; }
        .sp-table tr:last-child td { border-bottom:0; }
        .sp-table td:last-child { color:#0f172a; font-weight:650; text-align:right; overflow-wrap:anywhere; }
        .sp-log { display:grid; gap:.55rem; }
        .sp-log-row { border:1px solid #e2e8f0; border-radius:12px; padding:.7rem; color:#475569; font-size:.8rem; line-height:1.45; }
        .sp-log-row strong { color:#0f172a; }
        .sp-empty { border:1px dashed #cbd5e1; background:#f8fafc; border-radius:12px; padding:.8rem; color:#64748b; font-size:.82rem; }
        @media (max-width:980px) { .sp-grid, .sp-kpis, .sp-info-grid { grid-template-columns:1fr; } .sp-head { flex-direction:column; align-items:flex-start; } .sp-actions { justify-content:flex-start; } }
    </style>

    <div class="sp-shell">
        <div class="sp-kpis">
            <div class="sp-kpi sp-kpi-blue"><span>Total PO</span><strong>{{ number_format((int) $summary['purchase_count'], 0, ',', '.') }}</strong></div>
            <div class="sp-kpi"><span>Total Pembelian</span><strong>Rp {{ number_format((float) $summary['total_purchase'], 0, ',', '.') }}</strong></div>
            <div class="sp-kpi sp-kpi-red"><span>Hutang Aktif</span><strong>Rp {{ number_format((float) $summary['open_debt'], 0, ',', '.') }}</strong></div>
            <div class="sp-kpi sp-kpi-amber"><span>Overdue</span><strong>{{ number_format((int) $summary['overdue_count'], 0, ',', '.') }} PO</strong></div>
        </div>

        <div class="sp-grid">
            <div class="sp-stack">
                <div class="sp-card">
                    <div class="sp-card-head"><h3 class="sp-card-title">Histori Pembelian</h3><span class="sp-chip sp-chip-draft">{{ number_format((int) $purchases->total(), 0, ',', '.') }} PO</span></div>
                    <div class="sp-card-body">
                        <div class="sp-list">
                            @forelse($purchases as $purchase)
                                <div class="sp-row">
                                    <div class="sp-row-head">
                                        <div>
                                            <a class="sp-row-title" href="{{ route('admin.supplier-purchases.show', $purchase) }}">{{ $purchase->number }}</a>
                                            <div class="sp-row-sub">Order {{ optional($purchase->ordered_at)->format('d/m/Y') ?: '-' }} - Due {{ optional($purchase->due_date)->format('d/m/Y') ?: '-' }} - {{ number_format((int) ($purchase->items_count ?? 0), 0, ',', '.') }} barang</div>
                                        </div>
                                        <div class="sp-money">
                                            <strong>Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</strong><br>
                                            <span class="sp-row-sub">Sisa Rp {{ number_format((float) $purchase->remaining_amount, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                    <div style="display:flex; gap:.45rem; flex-wrap:wrap;">
                                        <span class="sp-chip sp-chip-{{ $purchase->status }}">{{ ($purchaseStatusLabels ?? [])[(string) $purchase->status] ?? ucfirst((string) $purchase->status) }}</span>
                                        <span class="sp-chip sp-chip-{{ $purchase->payment_status }}">{{ ($paymentStatusLabels ?? [])[(string) $purchase->payment_status] ?? ucfirst((string) $purchase->payment_status) }}</span>
                                        <span class="sp-chip sp-chip-draft">{{ number_format((int) ($purchase->attachments_count ?? 0), 0, ',', '.') }} lampiran</span>
                                    </div>
                                </div>
                            @empty
                                <div class="sp-empty">Belum ada pembelian untuk supplier ini.</div>
                            @endforelse
                        </div>
                        @if($purchases->hasPages())
                            <div style="margin-top:1rem;">{{ $purchases->links() }}</div>
                        @endif
                    </div>
                </div>

                <div class="sp-card">
                    <div class="sp-card-head"><h3 class="sp-card-title">Audit Supplier</h3></div>
                    <div class="sp-card-body"><div class="sp-log">
                        @forelse($auditLogs as $log)
                            <div class="sp-log-row"><strong>{{ ($auditActionLabels ?? [])[(string) $log->action] ?? str_replace('_', ' ', (string) $log->action) }}</strong><br>{{ optional($log->created_at)->format('d/m/Y H:i') }} oleh {{ $log->user?->name ?? '-' }}</div>
                        @empty
                            <div class="sp-empty">Belum ada audit khusus supplier ini.</div>
                        @endforelse
                    </div></div>
                </div>
            </div>

            <div class="sp-stack">
                <div class="sp-card">
                    <div class="sp-card-head"><h3 class="sp-card-title">Profil Supplier</h3></div>
                    <div class="sp-card-body">
                        <div class="sp-info-grid">
                            <div class="sp-info"><span>Status</span><strong><span class="sp-chip {{ $supplier->is_active ? 'sp-chip-active' : 'sp-chip-inactive' }}">{{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}</span></strong></div>
                            <div class="sp-info"><span>Order Terakhir</span><strong>{{ $summary['last_order'] }}</strong></div>
                        </div>
                        <table class="sp-table" style="margin-top:.8rem;">
                            <tr><td>Kode</td><td>{{ $supplier->code }}</td></tr>
                            <tr><td>No HP</td><td>{{ $supplier->phone ?: '-' }}</td></tr>
                            <tr><td>Email</td><td>{{ $supplier->email ?: '-' }}</td></tr>
                            <tr><td>Alamat</td><td>{{ $supplier->address ?: '-' }}</td></tr>
                            <tr><td>Catatan</td><td>{{ $supplier->note ?: '-' }}</td></tr>
                        </table>
                    </div>
                </div>

                <div class="sp-card">
                    <div class="sp-card-head"><h3 class="sp-card-title">Ringkasan Pembayaran</h3></div>
                    <div class="sp-card-body">
                        <table class="sp-table">
                            <tr><td>Total Pembelian</td><td>Rp {{ number_format((float) $summary['total_purchase'], 0, ',', '.') }}</td></tr>
                            <tr><td>Sudah Dibayar</td><td>Rp {{ number_format((float) $summary['paid_total'], 0, ',', '.') }}</td></tr>
                            <tr><td>Hutang Aktif</td><td>Rp {{ number_format((float) $summary['open_debt'], 0, ',', '.') }}</td></tr>
                            <tr><td>Overdue</td><td>{{ number_format((int) $summary['overdue_count'], 0, ',', '.') }} PO</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
