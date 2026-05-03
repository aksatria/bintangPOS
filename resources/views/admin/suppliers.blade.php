<x-app-layout>
    <x-slot name="header">
        <div class="ux-head intro-y">
            <div>
                <p class="ux-kicker">Master Data</p>
                <h2 class="ux-title">Supplier & Pembelian</h2>
                <p class="ux-sub">Manajemen vendor dan kontrol hutang supplier per cabang.</p>
            </div>
        </div>
    </x-slot>

    <style>
        .ux-shell { max-width: 1280px; margin: 0 auto; }
        .ux-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; }
        .ux-kicker { margin: 0; font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 700; }
        .ux-title { margin: .2rem 0 0; font-size: 1.95rem; line-height: 1.1; color: #0f172a; font-weight: 800; }
        .ux-sub { margin: .36rem 0 0; color: #64748b; font-size: .9rem; }
        .ux-alert { border-radius: 12px; border: 1px solid; padding: .8rem .92rem; font-size: .82rem; }
        .ux-alert-ok { border-color: #bbf7d0; background: #ecfdf5; color: #047857; }
        .ux-alert-err { border-color: #fecaca; background: #fff1f2; color: #be123c; }
        .ux-layout { display: grid; gap: 1rem; grid-template-columns: 1fr; align-items: start; }
        @media (min-width: 1120px) { .ux-layout { grid-template-columns: minmax(0,1.35fr) minmax(380px,.65fr); } }
        .ux-stack { display: grid; gap: 1rem; }
        .ux-side { display: grid; gap: 1rem; position: sticky; top: 1rem; }
        @media (max-width: 1119px) { .ux-side { position: static; } }
        .ux-card { background: #fff; border: 1px solid #dbe4f0; border-radius: 14px; box-shadow: 0 10px 22px rgba(15,23,42,.04); overflow: hidden; }
        .ux-card-head { padding: .94rem 1rem; border-bottom: 1px solid #e7eef7; background: linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%); }
        .ux-card-title { margin: 0; font-size: .83rem; text-transform: uppercase; letter-spacing: .08em; color: #334155; font-weight: 700; }
        .ux-card-body { padding: 1rem; }
        .ux-kpi-grid { display:grid; gap:.72rem; grid-template-columns:repeat(3,minmax(0,1fr)); }
        @media (max-width: 900px) { .ux-kpi-grid { grid-template-columns:1fr; } }
        .ux-kpi { border:1px solid #e2e8f0; border-radius:12px; padding:.72rem .8rem; background:#fff; }
        .ux-kpi-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; color:#64748b; font-weight:700; }
        .ux-kpi-value { font-size:1.15rem; font-weight:800; color:#0f172a; margin-top:.24rem; }
        .ux-kpi-value.warn { color:#c2410c; }
        .ux-kpi-value.danger { color:#be123c; }
        .ux-filter-grid { display: grid; gap: .6rem; grid-template-columns: 1fr; }
        @media (min-width: 860px) { .ux-filter-grid { grid-template-columns: 1fr 180px auto; } }
        .ux-form-grid { display: grid; gap: .68rem; }
        .ux-field { display: grid; gap: .32rem; }
        .ux-label { font-size: .72rem; letter-spacing: .05em; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .ux-input, .ux-select, .ux-textarea { width: 100%; min-height: 2.56rem; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #0f172a; font-size: .9rem; padding: 0 .72rem; }
        .ux-textarea { min-height: 84px; padding: .58rem .72rem; resize: vertical; }
        .ux-input:focus, .ux-select:focus, .ux-textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.16); }
        .ux-btn { min-height: 2.46rem; padding: 0 .92rem; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: .8rem; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; white-space: nowrap; }
        .ux-btn:hover { background: #f8fafc; }
        .ux-btn-primary { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        .ux-btn-primary:hover { background: #1e40af; border-color: #1e40af; }
        .ux-btn-block { width: 100%; }
        .ux-table-wrap { overflow: hidden; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ux-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .ux-table th, .ux-table td { padding: .72rem .66rem; border-bottom: 1px solid #e2e8f0; font-size: .8rem; text-align: left; vertical-align: top; }
        .ux-table th { background: #f8fafc; color: #475569; font-weight: 700; white-space: nowrap; }
        .ux-table td { color: #334155; }
        .ux-cell-title { font-weight: 600; color:#0f172a; }
        .ux-cell-sub { color:#64748b; margin-top:2px; }
        .ux-contact-grid { display:grid; gap:.14rem; margin-top:.34rem; color:#64748b; font-size:.76rem; line-height:1.35; }
        .ux-contact-grid span { overflow-wrap:anywhere; }
        .ux-auto-code-note { border:1px dashed #bfdbfe; background:#eff6ff; color:#1e40af; border-radius:10px; padding:.62rem .72rem; font-size:.78rem; line-height:1.4; }
        .ux-collapsible-card > summary { cursor:pointer; list-style:none; display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
        .ux-collapsible-card > summary::-webkit-details-marker { display:none; }
        .ux-card-toggle-wrap { display:inline-flex; align-items:center; gap:.44rem; color:#64748b; font-size:.78rem; font-weight:600; text-transform:none; letter-spacing:0; }
        .ux-card-toggle-text::before { content:'Buka'; }
        .ux-collapsible-card[open] .ux-card-toggle-text::before { content:'Tutup'; }
        .ux-card-toggle { width:1.75rem; height:1.75rem; border-radius:999px; border:1px solid #cbd5e1; color:#64748b; background:#fff; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; font-size:.92rem; line-height:1; }
        .ux-card-toggle::before { content:'+'; }
        .ux-collapsible-card[open] .ux-card-toggle::before { content:'-'; }
        .ux-contact-toggle { margin-top:.36rem; }
        .ux-contact-toggle summary { cursor:pointer; list-style:none; color:#1d4ed8; font-size:.76rem; font-weight:600; display:inline-flex; align-items:center; gap:.35rem; }
        .ux-contact-toggle summary::-webkit-details-marker { display:none; }
        .ux-contact-toggle summary::before { content:'+'; width:1rem; height:1rem; border:1px solid #bfdbfe; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; font-size:.68rem; line-height:1; }
        .ux-contact-toggle[open] summary::before { content:'-'; }
        .ux-chip { display: inline-flex; align-items: center; padding: .2rem .54rem; border-radius: 999px; border: 1px solid; font-size: .67rem; letter-spacing: 0; font-weight: 800; white-space: nowrap; }
        .ux-chip-active, .ux-chip-paid { color: #047857; border-color: #a7f3d0; background: #ecfdf5; }
        .ux-chip-inactive, .ux-chip-overdue { color: #9f1239; border-color: #fecdd3; background: #fff1f2; }
        .ux-chip-partial { color: #c2410c; border-color: #fdba74; background: #fff7ed; }
        .ux-chip-draft { color:#1d4ed8; border-color:#bfdbfe; background:#eff6ff; }
        .ux-chip-received { color:#047857; border-color:#a7f3d0; background:#ecfdf5; }
        .ux-chip-cancelled { color:#475569; border-color:#cbd5e1; background:#f8fafc; }
        .ux-chip-term { color:#a16207; border-color:#fde68a; background:#fffbeb; }
        .ux-chip-approval { color:#92400e; border-color:#fcd34d; background:#fffbeb; }
        .ux-mini-box { border: 1px solid #e2e8f0; border-radius: 12px; padding: .72rem; background: #fafcff; }
        .ux-mini-head { display:flex; align-items:center; justify-content:space-between; gap:.6rem; margin-bottom:.58rem; }
        .ux-row-remove { min-height:1.9rem; padding:0 .55rem; border-radius:8px; font-size:.72rem; color:#be123c; border:1px solid #fecdd3; background:#fff1f2; }
        .ux-row-remove:hover { background:#ffe4e6; }
        .ux-help { margin: .24rem 0 0; color:#64748b; font-size:.76rem; line-height:1.45; }
        .ux-footnote { color:#64748b; font-size:.72rem; line-height:1.45; }
        .ux-footnote-box { border:1px dashed #cbd5e1; background:#f8fafc; border-radius:10px; padding:.58rem .68rem; color:#64748b; font-size:.72rem; line-height:1.45; }
        .ux-entry-grid { display:grid; gap:.5rem; grid-template-columns: 1fr; }
        .ux-entry-numbers { display:grid; gap:.5rem; grid-template-columns:.65fr 1fr; }
        .ux-money-entry { text-align:right; font-weight:700; letter-spacing:.01em; }
        .ux-entry-actions { display:grid; gap:.5rem; grid-template-columns:1fr; }
        .ux-item-list { display:grid; gap:.5rem; }
        .ux-item-row { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.65rem; align-items:center; border:1px solid #e2e8f0; border-radius:12px; padding:.62rem .68rem; background:#f8fafc; }
        .ux-item-name { font-weight:600; color:#0f172a; line-height:1.25; }
        .ux-item-meta { margin-top:.2rem; color:#64748b; font-size:.78rem; }
        .ux-item-total { color:#1d4ed8; font-weight:700; }
        .ux-empty-state { color:#64748b; font-size:.82rem; border:1px dashed #cbd5e1; border-radius:12px; padding:.72rem; background:#f8fafc; }
        .ux-two-col { display:grid; gap:.5rem; grid-template-columns:1fr 1fr; }
        @media (max-width: 720px) { .ux-entry-numbers { grid-template-columns:1fr; } }
        @media (max-width: 620px) { .ux-two-col { grid-template-columns:1fr; } }
        .ux-purchase-list { display: grid; gap: .72rem; }
        .ux-purchase-row { display: grid; grid-template-columns: minmax(180px,1fr) minmax(160px,.9fr) minmax(180px,1fr); gap: .75rem; align-items: start; border: 1px solid #e2e8f0; border-radius: 12px; padding: .82rem; background: #fff; }
        .ux-purchase-meta { display: grid; gap: .24rem; min-width: 0; }
        .ux-schedule-box { display:grid; gap:.26rem; max-width:190px; }
        .ux-schedule-line { display:grid; grid-template-columns:46px auto; gap:.35rem; align-items:baseline; font-size:.8rem; }
        .ux-schedule-line span:first-child { color:#64748b; }
        .ux-schedule-line strong { color:#0f172a; font-weight:600; text-align:left; }
        .ux-purchase-actions { grid-column: 1 / -1; display: grid; gap: .5rem; padding-top: .72rem; border-top: 1px solid #e2e8f0; }
        .ux-payment-section { margin-top:.42rem; padding-top:.9rem; border-top:1px dashed #cbd5e1; }
        .ux-money-box { display: grid; gap: .26rem; max-width: 190px; }
        .ux-money-line { display: grid; grid-template-columns: 46px auto; gap: .35rem; align-items: baseline; font-size: .8rem; }
        .ux-money-line span:first-child { color: #64748b; }
        .ux-money-line strong { color: #0f172a; text-align: left; }
        .ux-money-line.total strong { color: #1d4ed8; }
        .ux-money-line.remaining strong { color: #be123c; }
        .ux-money-line.remaining.is-clear strong { color: #047857; }
        .ux-history-items { grid-column:1 / -1; border-top:1px solid #e2e8f0; padding-top:.72rem; }
        .ux-history-items summary { cursor:pointer; color:#334155; font-size:.78rem; font-weight:600; list-style:none; display:flex; align-items:center; justify-content:space-between; gap:.6rem; }
        .ux-history-items summary::-webkit-details-marker { display:none; }
        .ux-history-items summary::after { content:'Buka'; color:#1d4ed8; font-size:.74rem; font-weight:600; }
        .ux-history-items[open] summary::after { content:'Tutup'; }
        .ux-history-body { display:grid; gap:.35rem; margin-top:.58rem; }
        .ux-history-item { display:flex; justify-content:space-between; gap:.6rem; color:#475569; font-size:.78rem; }
        .ux-history-item strong { color:#0f172a; font-weight:600; }
        .ux-inline-form { display:grid; grid-template-columns: minmax(180px,1fr) 160px 120px; gap:.5rem; align-items:center; }
        .ux-inline-form .ux-input, .ux-inline-form .ux-select { min-height:2.2rem; }
        .ux-edit-draft { grid-column:1 / -1; border-top:1px solid #e2e8f0; padding-top:.72rem; }
        .ux-edit-draft summary { cursor:pointer; color:#1d4ed8; font-size:.8rem; font-weight:600; list-style:none; display:inline-flex; align-items:center; gap:.4rem; }
        .ux-edit-draft summary::-webkit-details-marker { display:none; }
        .ux-edit-draft summary::before { content:'+'; width:1.12rem; height:1.12rem; border:1px solid #bfdbfe; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; font-size:.72rem; line-height:1; }
        .ux-edit-draft[open] summary::before { content:'-'; }
        .ux-edit-grid { display:grid; gap:.6rem; margin-top:.72rem; }
        .ux-draft-action-row { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; border-top:1px solid #e2e8f0; padding-top:.72rem; }
        .ux-draft-action-row .ux-edit-draft { border-top:0; padding-top:0; flex:1 1 auto; min-width:0; }
        .ux-draft-action-main { flex:0 0 auto; display:grid; justify-items:end; gap:.18rem; max-width:260px; text-align:right; }
        @media (max-width: 720px) { .ux-draft-action-row { flex-direction:column; } .ux-draft-action-main { justify-items:start; text-align:left; max-width:none; } }
        .ux-card .pos-page-btn { display:inline-flex; align-items:center; justify-content:center; text-decoration:none; }
        .ux-ajax-panel { transition: opacity .16s ease, transform .16s ease; }
        .ux-ajax-panel.is-loading { opacity:.48; transform:translateY(2px); pointer-events:none; }
        @media (max-width: 1280px) { .ux-purchase-row { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 860px) { .ux-inline-form { grid-template-columns: 1fr; } }
        @media (max-width: 720px) { .ux-purchase-row { grid-template-columns: 1fr; } }
    </style>

    <div class="ux-shell intro-y space-y-4">
        @if (session('status'))
            <div class="ux-alert ux-alert-ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="ux-alert ux-alert-err">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="ux-layout">
            <div class="ux-stack">
                <div class="ux-card">
                    <div class="ux-card-head"><h3 class="ux-card-title">Ringkasan Hutang Supplier</h3></div>
                    <div class="ux-card-body">
                        <div class="ux-kpi-grid">
                            <div class="ux-kpi"><div class="ux-kpi-label">Outstanding</div><div class="ux-kpi-value">Rp {{ number_format((float) ($supplierDebtSummary['total_outstanding'] ?? 0), 0, ',', '.') }}</div></div>
                            <div class="ux-kpi"><div class="ux-kpi-label">Jatuh Tempo 7 Hari</div><div class="ux-kpi-value warn">Rp {{ number_format((float) ($supplierDebtSummary['due_this_week'] ?? 0), 0, ',', '.') }}</div></div>
                            <div class="ux-kpi"><div class="ux-kpi-label">Overdue</div><div class="ux-kpi-value danger">Rp {{ number_format((float) ($supplierDebtSummary['overdue_total'] ?? 0), 0, ',', '.') }}</div></div>
                        </div>
                    </div>
                </div>

                <details class="ux-card ux-collapsible-card ux-ajax-panel" id="supplier-list-panel" data-ajax-panel="supplier-list">
                    <summary class="ux-card-head"><h3 class="ux-card-title">Daftar Supplier</h3><span class="ux-card-toggle-wrap"><span class="ux-card-toggle-text"></span><span class="ux-card-toggle" aria-hidden="true"></span></span></summary>
                    <div class="ux-card-body space-y-4">
                        <form method="GET" action="{{ route('admin.suppliers.index') }}" class="ux-filter-grid">
                            <input class="ux-input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama supplier, kode, atau no HP">
                            <select class="ux-select" name="status">
                                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Semua Status</option>
                                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                            </select>
                            <button class="ux-btn ux-btn-primary" type="submit">Terapkan Filter</button>
                        </form>

                        <div class="ux-table-wrap">
                            <table class="ux-table">
                                <thead><tr><th>Supplier & Kontak</th><th>Status</th><th>Aksi</th></tr></thead>
                                <tbody>
                                @forelse($suppliers as $supplier)
                                    <tr>
                                        <td>
                                            <div class="ux-cell-title">{{ $supplier->name }}</div>
                                            <details class="ux-contact-toggle">
                                                <summary>Detail kontak</summary>
                                                <div class="ux-contact-grid">
                                                    <span>Kode: {{ $supplier->code }}</span>
                                                    <span>No HP: {{ $supplier->phone ?: '-' }}</span>
                                                    <span>Email: {{ $supplier->email ?: '-' }}</span>
                                                    <span>Alamat: {{ $supplier->address ?: '-' }}</span>
                                                </div>
                                            </details>
                                        </td>
                                        <td><span class="ux-chip {{ $supplier->is_active ? 'ux-chip-active' : 'ux-chip-inactive' }}">{{ $supplier->is_active ? 'AKTIF' : 'NONAKTIF' }}</span></td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}" data-ajax-form="1">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="name" value="{{ $supplier->name }}">
                                                <input type="hidden" name="code" value="{{ $supplier->code }}">
                                                <input type="hidden" name="phone" value="{{ $supplier->phone }}">
                                                <input type="hidden" name="email" value="{{ $supplier->email }}">
                                                <input type="hidden" name="address" value="{{ $supplier->address }}">
                                                <input type="hidden" name="note" value="{{ $supplier->note }}">
                                                <input type="hidden" name="is_active" value="{{ $supplier->is_active ? 0 : 1 }}">
                                                <button class="ux-btn" type="submit">{{ $supplier->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-slate-500">Belum ada supplier.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($suppliers->hasPages())
                            @php
                                $supplierCurrent = $suppliers->currentPage();
                                $supplierLast = $suppliers->lastPage();
                                $supplierStart = max(1, $supplierCurrent - 2);
                                $supplierEnd = min($supplierLast, $supplierCurrent + 2);
                            @endphp
                            <div class="pos-pagination">
                                <p class="pos-pagination__summary">
                                    Menampilkan {{ number_format((int) $suppliers->firstItem(), 0, ',', '.') }}-{{ number_format((int) $suppliers->lastItem(), 0, ',', '.') }} dari {{ number_format((int) $suppliers->total(), 0, ',', '.') }} supplier
                                </p>
                                <div class="pos-pagination__actions">
                                    @if($suppliers->onFirstPage())
                                        <button type="button" class="pos-page-btn" disabled>Awal</button>
                                        <button type="button" class="pos-page-btn" disabled>Sebelumnya</button>
                                    @else
                                        <a class="pos-page-btn" href="{{ $suppliers->url(1) }}">Awal</a>
                                        <a class="pos-page-btn" href="{{ $suppliers->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                                    @endif
                                    @for($page = $supplierStart; $page <= $supplierEnd; $page++)
                                        @if($page === $supplierCurrent)
                                            <button type="button" class="pos-page-btn is-active" disabled>{{ $page }}</button>
                                        @else
                                            <a class="pos-page-btn" href="{{ $suppliers->url($page) }}">{{ $page }}</a>
                                        @endif
                                    @endfor
                                    @if($suppliers->hasMorePages())
                                        <a class="pos-page-btn" href="{{ $suppliers->nextPageUrl() }}" rel="next">Berikutnya</a>
                                        <a class="pos-page-btn" href="{{ $suppliers->url($supplierLast) }}">Akhir</a>
                                    @else
                                        <button type="button" class="pos-page-btn" disabled>Berikutnya</button>
                                        <button type="button" class="pos-page-btn" disabled>Akhir</button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </details>

                <div class="ux-card ux-ajax-panel" id="supplier-purchases-panel" data-ajax-panel="supplier-purchases">
                    <div class="ux-card-head"><h3 class="ux-card-title">Pembelian Supplier Terakhir</h3></div>
                    <div class="ux-card-body">
                        <div class="ux-footnote-box mb-3">
                            Catatan: <strong>Draft</strong> diajukan dulu ke owner lewat tombol <strong>Ajukan Persetujuan</strong>. Setelah disetujui, tombol <strong>Barang Diterima</strong> bisa dipakai untuk menandai barang sudah datang dan mengunci draft.
                        </div>
                        <div class="ux-purchase-list">
                            @forelse($purchases as $purchase)
                                @php
                                    $payStatus = (string) ($purchase->payment_status ?? 'unpaid');
                                    $purchaseApproval = (array) ($supplierPurchaseApprovals[(int) $purchase->id] ?? []);
                                    $approvalId = (int) ($purchaseApproval['id'] ?? 0);
                                    $approvalStatus = (string) ($purchaseApproval['status'] ?? '');
                                    $approvalReviewNote = (string) ($purchaseApproval['review_note'] ?? '');
                                    $approvalReviewedAt = (string) ($purchaseApproval['reviewed_at'] ?? '');
                                    $purchaseApproved = $approvalStatus === 'approved';
                                    $purchasePendingApproval = $approvalStatus === 'pending';
                                    $purchaseRejectedApproval = $approvalStatus === 'rejected';
                                @endphp
                                <div class="ux-purchase-row">
                                    <div class="ux-purchase-meta">
                                        <div class="ux-label">Dokumen</div>
                                        <div class="ux-cell-title">{{ $purchase->number }}</div>
                                        <div class="ux-cell-sub">{{ $purchase->supplier?->name ?? '-' }}</div>
                                        <div class="mt-1">
                                            <span class="ux-chip {{ $purchase->status === 'draft' ? 'ux-chip-draft' : ($purchase->status === 'received' ? 'ux-chip-received' : 'ux-chip-cancelled') }}">Barang: {{ strtoupper($purchase->status) }}</span>
                                        </div>
                                    </div>
                                    <div class="ux-purchase-meta">
                                        <div class="ux-label">Jadwal</div>
                                        <div class="ux-schedule-box">
                                            <div class="ux-schedule-line"><span>Order</span><strong>{{ optional($purchase->ordered_at)->format('d/m/Y') }}</strong></div>
                                            <div class="ux-schedule-line"><span>Due</span><strong>{{ optional($purchase->due_date)->format('d/m/Y') ?: '-' }}</strong></div>
                                        </div>
                                        <div class="mt-1"><span class="ux-chip ux-chip-term">Termin: {{ (int) ($purchase->payment_term_days ?? 0) }} hari</span></div>
                                    </div>
                                    <div class="ux-purchase-meta">
                                        <div class="ux-label">Nilai</div>
                                        @php
                                            $remainingAmount = (float) ($purchase->remaining_amount ?? $purchase->total_amount);
                                        @endphp
                                        <div class="ux-money-box">
                                            <div class="ux-money-line total"><span>Total</span><strong>Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</strong></div>
                                            <div class="ux-money-line remaining {{ $remainingAmount <= 0 ? 'is-clear' : '' }}"><span>Sisa</span><strong>Rp {{ number_format($remainingAmount, 0, ',', '.') }}</strong></div>
                                        </div>
                                        @if((float) ($purchase->shipping_amount ?? 0) > 0)
                                            <div class="ux-cell-sub">Ongkir: Rp {{ number_format((float) $purchase->shipping_amount, 0, ',', '.') }}</div>
                                        @endif
                                        @if((float) ($purchase->paid_amount ?? 0) > 0)
                                            <div class="ux-cell-sub">Sudah bayar: Rp {{ number_format((float) $purchase->paid_amount, 0, ',', '.') }}</div>
                                        @endif
                                        <div class="mt-1"><span class="ux-chip {{ $payStatus === 'paid' ? 'ux-chip-paid' : ($payStatus === 'overdue' ? 'ux-chip-overdue' : ($payStatus === 'partial' ? 'ux-chip-partial' : 'ux-chip-inactive')) }}">{{ strtoupper($payStatus) }}</span></div>
                                        @if($purchaseApproved)
                                            <div class="mt-1"><span class="ux-chip ux-chip-active">Disetujui owner</span></div>
                                        @elseif($purchasePendingApproval)
                                            <div class="mt-1"><span class="ux-chip ux-chip-approval">Menunggu persetujuan owner</span></div>
                                        @elseif($purchaseRejectedApproval)
                                            <div class="mt-1"><span class="ux-chip ux-chip-overdue">Persetujuan ditolak</span></div>
                                            @if($approvalReviewNote !== '')
                                                <div class="ux-cell-sub">Alasan: {{ $approvalReviewNote }}</div>
                                            @elseif($approvalReviewedAt !== '')
                                                <div class="ux-cell-sub">Ditolak pada {{ $approvalReviewedAt }}</div>
                                            @endif
                                        @endif
                                    </div>
                                    <details class="ux-history-items">
                                        <summary>Barang dipesan ({{ number_format((int) ($purchase->items_count ?? $purchase->items->count()), 0, ',', '.') }})</summary>
                                        <div class="ux-history-body">
                                            @forelse($purchase->items as $item)
                                                <div class="ux-history-item">
                                                    <span><strong>{{ $item->product_name }}</strong> x {{ number_format((int) $item->quantity, 0, ',', '.') }}</span>
                                                    <span>Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</span>
                                                </div>
                                            @empty
                                                <div class="ux-cell-sub">Belum ada detail barang.</div>
                                            @endforelse
                                            @if($purchase->items_count > $purchase->items->count())
                                                <div class="ux-cell-sub">+{{ $purchase->items_count - $purchase->items->count() }} barang lainnya</div>
                                            @endif
                                        </div>
                                    </details>
                                    <div class="ux-purchase-actions">
                                        @if($purchase->status === 'draft')
                                            @php
                                                $editItemsText = $purchase->items->map(fn ($item) => $item->product_name.' | '.(int) $item->quantity.' | '.number_format((float) $item->unit_cost, 0, ',', '.'))->implode("\n");
                                            @endphp
                                            <div class="ux-draft-action-row">
                                                <details class="ux-edit-draft">
                                                    <summary>Edit Draft</summary>
                                                    <form method="POST" action="{{ route('admin.supplier-purchases.update', $purchase) }}" class="ux-edit-grid" data-ajax-form="1">
                                                        @csrf
                                                        @method('PUT')
                                                        <label class="ux-field">
                                                            <span class="ux-label">Supplier</span>
                                                            <select class="ux-select" name="supplier_id" required>
                                                                @foreach($supplierOptions as $supplierOption)
                                                                    <option value="{{ $supplierOption->id }}" @selected((int) $purchase->supplier_id === (int) $supplierOption->id)>{{ $supplierOption->name }} ({{ $supplierOption->code }})</option>
                                                                @endforeach
                                                            </select>
                                                        </label>
                                                        <div class="ux-two-col">
                                                            <label class="ux-field"><span class="ux-label">Tanggal Order</span><input class="ux-input" type="date" name="ordered_at" value="{{ optional($purchase->ordered_at)->toDateString() }}" required></label>
                                                            <label class="ux-field"><span class="ux-label">Termin Pembelian (Hari)</span><input class="ux-input" type="number" min="0" max="365" name="payment_term_days" value="{{ (int) ($purchase->payment_term_days ?? 0) }}"></label>
                                                        </div>
                                                        <label class="ux-field">
                                                            <span class="ux-label">Barang Pembelian</span>
                                                            <textarea class="ux-textarea" name="bulk_items" rows="5" required>{{ $editItemsText }}</textarea>
                                                            <span class="ux-help">Format: Nama Produk | Qty | Harga. Edit semua baris di sini, lalu simpan.</span>
                                                        </label>
                                                        <div class="ux-two-col">
                                                            <label class="ux-field"><span class="ux-label">Diskon</span><input class="ux-input" type="number" min="0" step="0.01" name="discount_amount" value="{{ (float) $purchase->discount_amount }}"></label>
                                                            <label class="ux-field"><span class="ux-label">Pajak</span><input class="ux-input" type="number" min="0" step="0.01" name="tax_amount" value="{{ (float) $purchase->tax_amount }}"></label>
                                                        </div>
                                                        <label class="ux-field"><span class="ux-label">Ongkir</span><input class="ux-input" type="number" min="0" step="0.01" name="shipping_amount" value="{{ (float) ($purchase->shipping_amount ?? 0) }}"></label>
                                                        <label class="ux-field"><span class="ux-label">Catatan</span><textarea class="ux-textarea" name="note" rows="2">{{ $purchase->note }}</textarea></label>
                                                        <button class="ux-btn ux-btn-primary" type="submit">Simpan Perubahan Draft</button>
                                                    </form>
                                                </details>
                                                <div class="ux-draft-action-main">
                                                    @if($purchaseApproved)
                                                        <form method="POST" action="{{ route('admin.supplier-purchases.receive', $purchase) }}" data-ajax-form="1">
                                                            @csrf
                                                            <button class="ux-btn ux-btn-primary" type="submit" onclick="return confirm('Tandai pembelian ini sudah diterima?')">Barang Diterima</button>
                                                        </form>
                                                    @elseif($purchasePendingApproval)
                                                        <div class="ux-footnote-box">
                                                            Pembelian sedang menunggu persetujuan owner.
                                                            @if(auth()->user()?->hasPermission('approvals.manage'))
                                                                <a class="text-blue-700 font-semibold" href="{{ route('admin.approvals.index', ['status' => 'pending', 'type' => 'supplier.purchase_approval']) }}">Buka Approval</a>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <form method="POST" action="{{ route('admin.supplier-purchases.request-approval', $purchase) }}" data-ajax-form="1">
                                                            @csrf
                                                            <button class="ux-btn ux-btn-primary" type="submit">{{ $purchaseRejectedApproval ? 'Ajukan Ulang Persetujuan' : 'Ajukan Persetujuan' }}</button>
                                                        </form>
                                                        <div class="ux-footnote">Owner perlu menyetujui draft.</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                        @if((float) ($purchase->remaining_amount ?? 0) > 0)
                                            @if($purchase->status !== 'draft' || $purchaseApproved)
                                                <form method="POST" action="{{ route('admin.supplier-purchases.pay', $purchase) }}" data-ajax-form="1" class="ux-inline-form ux-payment-section">
                                                    @csrf
                                                    <input class="ux-input" type="number" min="1" step="0.01" name="amount" placeholder="Nominal" required>
                                                    <select class="ux-select" name="payment_method" required>
                                                        <option value="cash">cash</option>
                                                        <option value="transfer">transfer</option>
                                                        <option value="debit">debit</option>
                                                        <option value="qris">qris</option>
                                                        <option value="e_wallet">e_wallet</option>
                                                    </select>
                                                    <button class="ux-btn ux-btn-primary" type="submit">Bayar</button>
                                                </form>
                                            @else
                                                <div class="ux-footnote">Pembayaran draft dibuka setelah pembelian disetujui owner.</div>
                                            @endif
                                        @elseif($purchase->status !== 'draft')
                                            <span class="text-slate-500">Selesai</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-slate-500">Belum ada transaksi pembelian supplier.</div>
                            @endforelse
                        </div>
                        @if($purchases->hasPages())
                            @php
                                $purchaseCurrent = $purchases->currentPage();
                                $purchaseLast = $purchases->lastPage();
                                $purchaseStart = max(1, $purchaseCurrent - 2);
                                $purchaseEnd = min($purchaseLast, $purchaseCurrent + 2);
                            @endphp
                            <div class="mt-4 pos-pagination">
                                <p class="pos-pagination__summary">
                                    Menampilkan {{ number_format((int) $purchases->firstItem(), 0, ',', '.') }}-{{ number_format((int) $purchases->lastItem(), 0, ',', '.') }} dari {{ number_format((int) $purchases->total(), 0, ',', '.') }} pembelian
                                </p>
                                <div class="pos-pagination__actions">
                                    @if($purchases->onFirstPage())
                                        <button type="button" class="pos-page-btn" disabled>Awal</button>
                                        <button type="button" class="pos-page-btn" disabled>Sebelumnya</button>
                                    @else
                                        <a class="pos-page-btn" href="{{ $purchases->url(1) }}">Awal</a>
                                        <a class="pos-page-btn" href="{{ $purchases->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                                    @endif
                                    @for($page = $purchaseStart; $page <= $purchaseEnd; $page++)
                                        @if($page === $purchaseCurrent)
                                            <button type="button" class="pos-page-btn is-active" disabled>{{ $page }}</button>
                                        @else
                                            <a class="pos-page-btn" href="{{ $purchases->url($page) }}">{{ $page }}</a>
                                        @endif
                                    @endfor
                                    @if($purchases->hasMorePages())
                                        <a class="pos-page-btn" href="{{ $purchases->nextPageUrl() }}" rel="next">Berikutnya</a>
                                        <a class="pos-page-btn" href="{{ $purchases->url($purchaseLast) }}">Akhir</a>
                                    @else
                                        <button type="button" class="pos-page-btn" disabled>Berikutnya</button>
                                        <button type="button" class="pos-page-btn" disabled>Akhir</button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="ux-side">
                <div class="ux-card">
                    <div class="ux-card-head"><h3 class="ux-card-title">Tambah Supplier</h3></div>
                    <div class="ux-card-body">
                        <form method="POST" action="{{ route('admin.suppliers.store') }}" class="ux-form-grid" data-ajax-form="1">
                            @csrf
                            <div class="ux-auto-code-note">Kode supplier akan dibuat otomatis saat disimpan.</div>
                            <label class="ux-field"><span class="ux-label">Nama Supplier</span><input class="ux-input" name="name" required></label>
                            <label class="ux-field"><span class="ux-label">No HP</span><input class="ux-input" name="phone"></label>
                            <label class="ux-field"><span class="ux-label">Email</span><input class="ux-input" name="email" type="email"></label>
                            <label class="ux-field"><span class="ux-label">Alamat</span><input class="ux-input" name="address"></label>
                            <button class="ux-btn ux-btn-primary ux-btn-block" type="submit">Simpan Supplier</button>
                        </form>
                    </div>
                </div>

                <div class="ux-card">
                    <div class="ux-card-head"><h3 class="ux-card-title">Buat Draft Pembelian</h3></div>
                    <div class="ux-card-body">
                        <form method="POST" action="{{ route('admin.supplier-purchases.store') }}" class="ux-form-grid" data-ajax-form="1">
                            @csrf
                            <label class="ux-field"><span class="ux-label">Supplier</span><select class="ux-select" name="supplier_id" id="supplier-purchase-supplier" required><option value="">Pilih supplier</option>@foreach($supplierOptions as $supplierOption)<option value="{{ $supplierOption->id }}">{{ $supplierOption->name }} ({{ $supplierOption->code }})</option>@endforeach</select></label>
                            <div class="ux-two-col"><label class="ux-field"><span class="ux-label">Tanggal Order</span><input class="ux-input" type="date" name="ordered_at" value="{{ now()->toDateString() }}" required></label><label class="ux-field"><span class="ux-label">Termin Pembelian (Hari)</span><input class="ux-input" type="number" min="0" max="365" name="payment_term_days" id="purchase-term-days" value="0"></label></div>
                            <div class="ux-mini-box">
                                <div class="ux-mini-head">
                                    <span class="ux-label">Tambah Produk</span>
                                    <span class="ux-help" style="margin:0;">Tanpa reload halaman</span>
                                </div>
                                <div class="ux-entry-grid">
                                    <label class="ux-field"><span class="ux-label">Produk</span><input class="ux-input" type="text" id="draft-item-name" placeholder="Ketik nama produk"></label>
                                    <div class="ux-entry-numbers">
                                        <label class="ux-field"><span class="ux-label">Qty</span><input class="ux-input" type="number" min="1" id="draft-item-qty" value="1"></label>
                                        <label class="ux-field"><span class="ux-label">Harga Beli</span><input class="ux-input ux-money-entry" type="text" inputmode="numeric" id="draft-item-cost" value="Rp 0"></label>
                                    </div>
                                </div>
                                <div class="ux-entry-actions mt-2">
                                    <button class="ux-btn ux-btn-primary ux-btn-block" type="button" id="add-draft-item">Tambah Produk</button>
                                </div>
                                <p class="ux-help">Tekan Enter pada harga beli atau klik tombol untuk memasukkan produk ke daftar sementara.</p>
                            </div>
                            <div class="ux-mini-box">
                                <div class="ux-mini-head">
                                    <span class="ux-label">Daftar Produk Pembelian</span>
                                    <span class="ux-item-total" id="draft-items-total">Rp 0</span>
                                </div>
                                <div id="purchase-items" class="ux-item-list">
                                    <div class="ux-empty-state" id="purchase-items-empty">Belum ada produk. Isi produk di atas lalu klik Tambah Produk.</div>
                                </div>
                            </div>
                            <label class="ux-field">
                                <span class="ux-label">Paste Banyak Produk (Opsional)</span>
                                <textarea class="ux-textarea" name="bulk_items" rows="6" placeholder="Contoh:
Paracetamol 500mg | 1000 | 850
Vitamin C 1000mg | 250 | 1200
Masker Medis | 50 | 18000"></textarea>
                                <span class="ux-help">Untuk order besar, paste dari Excel/CSV. Format per baris: Nama Produk | Qty | Harga. Bisa pakai pemisah tab, titik koma, koma, atau garis vertikal.</span>
                            </label>
                            <div class="ux-two-col"><label class="ux-field"><span class="ux-label">Diskon</span><input class="ux-input" type="number" min="0" step="0.01" name="discount_amount" value="0"></label><label class="ux-field"><span class="ux-label">Pajak</span><input class="ux-input" type="number" min="0" step="0.01" name="tax_amount" value="0"></label></div>
                            <label class="ux-field"><span class="ux-label">Ongkir</span><input class="ux-input" type="number" min="0" step="0.01" name="shipping_amount" value="0"></label>
                            <div class="ux-two-col"><label class="ux-field"><span class="ux-label">Bayar Dimuka / DP</span><input class="ux-input" type="number" min="0" step="0.01" name="down_payment_amount" value="0"></label><label class="ux-field"><span class="ux-label">Metode DP</span><select class="ux-select" name="down_payment_method"><option value="cash">cash</option><option value="transfer">transfer</option><option value="debit">debit</option><option value="qris">qris</option><option value="e_wallet">e_wallet</option></select></label></div>
                            <div class="ux-footnote">Setelah draft disimpan, ajukan persetujuan owner dari daftar pembelian.</div>
                            <button class="ux-btn ux-btn-primary ux-btn-block" type="submit">Simpan Draft Pembelian</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const toast = (message, type = 'success') => (window.AppUI?.toast ? window.AppUI.toast(String(message || ''), type) : null);
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const itemWrap = document.getElementById('purchase-items');
            const itemEmpty = document.getElementById('purchase-items-empty');
            const itemTotal = document.getElementById('draft-items-total');
            const itemName = document.getElementById('draft-item-name');
            const itemQty = document.getElementById('draft-item-qty');
            const itemCost = document.getElementById('draft-item-cost');
            const addItemBtn = document.getElementById('add-draft-item');
            let itemIndex = 0;

            const formatRupiah = (value) => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(value || 0);

            const parseRupiah = (value) => Number(String(value || '').replace(/[^\d]/g, '')) || 0;

            const syncMoneyInput = (input) => {
                if (!input) return;
                input.value = formatRupiah(parseRupiah(input.value));
            };

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));

            const refreshDraftTotal = () => {
                const total = Array.from(itemWrap?.querySelectorAll('[data-line-total]') || [])
                    .reduce((sum, row) => sum + Number(row.dataset.lineTotal || 0), 0);
                if (itemTotal) itemTotal.textContent = formatRupiah(total);
                if (itemEmpty) itemEmpty.style.display = itemWrap?.querySelector('[data-draft-item="1"]') ? 'none' : 'block';
            };

            const bindRemoveButtons = () => {
                itemWrap?.querySelectorAll('[data-remove-item="1"]').forEach((button) => {
                    button.onclick = () => {
                        button.closest('[data-draft-item="1"]')?.remove();
                        refreshDraftTotal();
                    };
                });
            };

            const addDraftItem = () => {
                if (!itemWrap || !itemName || !itemQty || !itemCost) return false;
                const name = itemName.value.trim();
                const qty = Math.max(Number(itemQty.value || 0), 1);
                const cost = parseRupiah(itemCost.value);
                if (name === '') {
                    itemName.focus();
                    return false;
                }
                if (cost <= 0) {
                    itemCost.focus();
                    return false;
                }

                const index = itemIndex++;
                const row = document.createElement('div');
                const lineTotal = qty * cost;
                row.className = 'ux-item-row';
                row.dataset.draftItem = '1';
                row.dataset.lineTotal = String(lineTotal);
                row.innerHTML = `<div><div class="ux-item-name">${escapeHtml(name)}</div><div class="ux-item-meta">${qty} x ${formatRupiah(cost)} = <span class="ux-item-total">${formatRupiah(lineTotal)}</span></div><input type="hidden" name="items[${index}][product_name]" value="${escapeHtml(name)}"><input type="hidden" name="items[${index}][quantity]" value="${qty}"><input type="hidden" name="items[${index}][unit_cost]" value="${cost}"></div><button class="ux-row-remove" type="button" data-remove-item="1">Hapus</button>`;
                itemWrap.appendChild(row);
                itemName.value = '';
                itemQty.value = '1';
                itemCost.value = formatRupiah(0);
                itemName.focus();
                bindRemoveButtons();
                refreshDraftTotal();
                return true;
            };

            addItemBtn?.addEventListener('click', addDraftItem);
            itemCost?.addEventListener('input', () => syncMoneyInput(itemCost));
            itemCost?.addEventListener('focus', () => itemCost.select());
            syncMoneyInput(itemCost);
            [itemName, itemQty, itemCost].forEach((input) => input?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (input === itemCost) {
                        addDraftItem();
                    } else if (input === itemName) {
                        itemQty?.focus();
                    } else {
                        itemCost?.focus();
                    }
                }
            }));
            bindRemoveButtons();
            refreshDraftTotal();

            const bindAjaxForms = (scope = document) => scope.querySelectorAll('form[data-ajax-form="1"]').forEach((form) => {
                if (form.dataset.ajaxBound === '1') return;
                form.dataset.ajaxBound = '1';
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (form.contains(addItemBtn) && itemName?.value.trim()) {
                        addDraftItem();
                    }
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) btn.disabled = true;
                    try {
                        const resp = await fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: new FormData(form) });
                        if (!resp.ok) {
                            const data = await resp.json().catch(() => ({}));
                            const msg = data?.message || Object.values(data?.errors || {})?.flat?.()[0] || 'Gagal menyimpan data.';
                            throw new Error(msg);
                        }
                        toast('Perubahan berhasil disimpan.', 'success');
                        setTimeout(() => window.location.reload(), 320);
                    } catch (err) {
                        toast(err?.message || 'Terjadi kesalahan.', 'error');
                    } finally {
                        if (btn) btn.disabled = false;
                    }
                });
            });

            bindAjaxForms();

            const replaceAjaxPanel = async (url, panelId) => {
                const panel = document.getElementById(panelId);
                if (!panel) return false;
                panel.classList.add('is-loading');

                try {
                    const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!resp.ok) throw new Error('Gagal memuat halaman.');
                    const html = await resp.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nextPanel = doc.getElementById(panelId);
                    if (!nextPanel) throw new Error('Panel tidak ditemukan.');

                    panel.replaceWith(nextPanel);
                    bindAjaxForms(nextPanel);
                    window.history.pushState({}, '', url);
                    return true;
                } catch (err) {
                    toast(err?.message || 'Gagal memuat pagination.', 'error');
                    return false;
                } finally {
                    document.getElementById(panelId)?.classList.remove('is-loading');
                }
            };

            document.addEventListener('click', async (event) => {
                const link = event.target.closest('a.pos-page-btn');
                if (!link || !link.closest('[data-ajax-panel]')) return;

                event.preventDefault();
                const panelId = link.closest('[data-ajax-panel]')?.id;
                if (!panelId) {
                    window.location.href = link.href;
                    return;
                }

                const ok = await replaceAjaxPanel(link.href, panelId);
                if (!ok) window.location.href = link.href;
            });
        })();
    </script>
</x-app-layout>
