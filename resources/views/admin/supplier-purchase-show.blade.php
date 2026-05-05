<x-app-layout>
    <x-slot name="header">
        <div class="po-head">
            <div>
                <p class="po-kicker">Pembelian Supplier</p>
                <h2 class="po-title">{{ $purchase->number }}</h2>
                <p class="po-sub">{{ $purchase->supplier?->name ?? '-' }}</p>
            </div>
            <div class="po-head-actions">
                <a class="po-btn" href="{{ route('admin.suppliers.index') }}">Kembali</a>
                <a class="po-btn po-btn-primary" href="{{ route('admin.supplier-purchases.pdf', $purchase) }}">Cetak PDF</a>
            </div>
        </div>
    </x-slot>

    <style>
        .po-shell { max-width:1120px; margin:0 auto; display:grid; gap:1rem; }
        .po-head { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; }
        .po-kicker { margin:0; font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; color:#64748b; font-weight:700; }
        .po-title { margin:.2rem 0 0; font-size:1.8rem; line-height:1.1; color:#0f172a; font-weight:800; }
        .po-sub { margin:.28rem 0 0; color:#64748b; font-size:.9rem; }
        .po-head-actions { display:flex; flex-wrap:wrap; gap:.5rem; justify-content:flex-end; }
        .po-btn { min-height:2.35rem; padding:0 .9rem; border:1px solid #cbd5e1; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; color:#334155; background:#fff; font-size:.82rem; font-weight:700; text-decoration:none; }
        .po-btn-primary { color:#fff; background:#1d4ed8; border-color:#1d4ed8; }
        .po-btn-danger { color:#be123c; border-color:#fecdd3; background:#fff1f2; }
        .po-card { background:#fff; border:1px solid #dbe4f0; border-radius:14px; box-shadow:0 10px 22px rgba(15,23,42,.04); overflow:hidden; }
        .po-card-head { padding:.9rem 1rem; border-bottom:1px solid #e7eef7; background:linear-gradient(180deg,#fbfdff 0%,#f8fafc 100%); display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
        .po-card-title { margin:0; font-size:.82rem; text-transform:uppercase; letter-spacing:.08em; color:#334155; font-weight:700; }
        .po-card-body { padding:1rem; }
        .po-kpi-grid { display:grid; gap:.7rem; grid-template-columns:repeat(3,minmax(0,1fr)); }
        .po-kpi { border:1px solid #e2e8f0; border-radius:12px; padding:.78rem; background:#fff; }
        .po-kpi span, .po-info span, .po-label { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:#64748b; font-weight:700; }
        .po-kpi strong { display:block; margin-top:.22rem; color:#0f172a; font-size:1.05rem; font-weight:800; }
        .po-kpi-total strong { color:#1d4ed8; }
        .po-kpi-remain strong { color:#be123c; }
        .po-info-grid { display:grid; gap:.7rem; grid-template-columns:repeat(2,minmax(0,1fr)); }
        .po-info { border:1px solid #e2e8f0; border-radius:12px; padding:.72rem; background:#f8fafc; }
        .po-info strong { display:block; margin-top:.18rem; color:#0f172a; font-size:.9rem; font-weight:650; overflow-wrap:anywhere; }
        .po-detail-columns { display:grid; gap:1rem; grid-template-columns:minmax(0,1.18fr) minmax(360px,.82fr); align-items:start; }
        .po-main-stack, .po-side-stack, .po-log, .po-attachment-form, .po-attachment-list { display:grid; gap:1rem; align-content:start; }
        .po-table-wrap { overflow:hidden; border:1px solid #e2e8f0; border-radius:12px; }
        .po-table { width:100%; border-collapse:collapse; }
        .po-table th, .po-table td { padding:.72rem; border-bottom:1px solid #e2e8f0; text-align:left; vertical-align:top; font-size:.82rem; }
        .po-table th { background:#f8fafc; color:#475569; text-transform:uppercase; letter-spacing:.05em; font-size:.7rem; font-weight:800; }
        .po-table tr:last-child td { border-bottom:0; }
        .po-money { text-align:right !important; white-space:nowrap; }
        .po-chip { display:inline-flex; align-items:center; border:1px solid; border-radius:999px; padding:.24rem .58rem; font-size:.7rem; font-weight:800; }
        .po-chip-draft { color:#1d4ed8; border-color:#bfdbfe; background:#eff6ff; }
        .po-chip-received, .po-chip-paid, .po-chip-approved { color:#047857; border-color:#a7f3d0; background:#ecfdf5; }
        .po-chip-partial_received, .po-chip-partial, .po-chip-pending { color:#c2410c; border-color:#fdba74; background:#fff7ed; }
        .po-chip-cancelled { color:#475569; border-color:#cbd5e1; background:#f8fafc; }
        .po-chip-unpaid, .po-chip-rejected { color:#be123c; border-color:#fecdd3; background:#fff1f2; }
        .po-log-row { border:1px solid #e2e8f0; border-radius:12px; padding:.7rem; background:#fff; font-size:.8rem; color:#475569; line-height:1.45; }
        .po-log-row strong { color:#0f172a; }
        .po-log-action { display:inline-flex; border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8; border-radius:999px; padding:.15rem .48rem; font-size:.68rem; font-weight:750; margin-bottom:.28rem; }
        .po-form-grid { display:grid; gap:.6rem; grid-template-columns:160px 1fr; }
        .po-label { margin-bottom:.3rem; }
        .po-input, .po-select, .po-textarea { width:100%; min-height:2.42rem; border:1px solid #cbd5e1; border-radius:10px; background:#fff; color:#0f172a; font-size:.86rem; padding:0 .72rem; }
        .po-textarea { padding:.58rem .72rem; min-height:68px; resize:vertical; }
        .po-file-help, .po-attachment-meta { margin-top:.25rem; color:#64748b; font-size:.74rem; }
        .po-attachment-row { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.7rem; align-items:start; border:1px solid #e2e8f0; border-radius:12px; padding:.7rem; background:#fff; }
        .po-attachment-row a, .po-proof-link { color:#1d4ed8; font-weight:700; overflow-wrap:anywhere; }
        .po-proof-form { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.45rem; margin-top:.55rem; align-items:end; }
        .po-proof-list { display:grid; gap:.3rem; margin-top:.5rem; }
        .po-recon-form { display:grid; gap:.65rem; margin-top:.85rem; border-top:1px dashed #cbd5e1; padding-top:.85rem; }
        .po-note { border:1px dashed #cbd5e1; background:#f8fafc; border-radius:12px; padding:.8rem; color:#475569; font-size:.82rem; line-height:1.5; white-space:pre-line; }
        .po-timeline { display:grid; gap:.55rem; }
        .po-timeline-step { display:grid; grid-template-columns:auto minmax(0,1fr); gap:.62rem; align-items:start; padding:.62rem; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; }
        .po-timeline-dot { width:1.55rem; height:1.55rem; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:800; color:#64748b; background:#fff; border:1px solid #cbd5e1; }
        .po-timeline-step.is-done { border-color:#bbf7d0; background:#ecfdf5; }
        .po-timeline-step.is-done .po-timeline-dot { color:#047857; border-color:#86efac; background:#dcfce7; }
        .po-timeline-step.is-current { border-color:#bfdbfe; background:#eff6ff; }
        .po-timeline-step.is-current .po-timeline-dot { color:#1d4ed8; border-color:#93c5fd; background:#dbeafe; }
        .po-timeline-title { color:#0f172a; font-size:.82rem; font-weight:750; }
        .po-timeline-text { margin-top:.12rem; color:#64748b; font-size:.75rem; line-height:1.35; }
        @media (max-width:980px) {
            .po-detail-columns, .po-kpi-grid, .po-info-grid { grid-template-columns:1fr; }
            .po-form-grid, .po-attachment-row { grid-template-columns:1fr; }
            .po-head { align-items:flex-start; flex-direction:column; }
            .po-head-actions { justify-content:flex-start; }
        }
    </style>

    <div class="po-shell">
        @php
            $orderedQty = (int) $purchase->items->sum('quantity');
            $receivedQty = (int) $purchase->items->sum('received_quantity');
            $returnedQty = (int) $purchase->items->sum('returned_quantity');
            $returnAmount = (float) $purchase->returns->sum('amount');
            $invoiceAmount = $purchase->supplier_invoice_amount !== null ? (float) $purchase->supplier_invoice_amount : null;
            $invoiceDifference = $invoiceAmount !== null ? $invoiceAmount - (float) $purchase->total_amount : null;
            $reconciliationLabel = match((string) ($purchase->reconciliation_status ?? 'unchecked')) {
                'matched' => 'Cocok',
                'mismatch' => 'Selisih',
                'dispute' => 'Dispute',
                default => 'Belum Dicek',
            };
            $hasPendingApproval = collect($approvalHistory ?? [])->contains(fn ($approval) => ($approval['is_current'] ?? false) && ($approval['status'] ?? '') === 'pending');
            $hasApprovedApproval = collect($approvalHistory ?? [])->contains(fn ($approval) => ($approval['is_current'] ?? false) && ($approval['status'] ?? '') === 'approved');
            $hasRejectedApproval = collect($approvalHistory ?? [])->contains(fn ($approval) => ($approval['is_current'] ?? false) && ($approval['status'] ?? '') === 'rejected');
            $timelineSteps = [
                ['title' => 'Draft dibuat', 'text' => 'Data supplier, barang, nilai, dan termin pembelian sudah tersimpan.', 'state' => 'done'],
                ['title' => $hasRejectedApproval ? 'Persetujuan ditolak' : ($hasApprovedApproval ? 'Disetujui owner' : ($hasPendingApproval ? 'Menunggu persetujuan' : 'Belum diajukan')), 'text' => $hasRejectedApproval ? 'Draft perlu diperbaiki atau diajukan ulang.' : ($hasApprovedApproval ? 'Pembelian boleh dilanjutkan ke penerimaan barang.' : ($hasPendingApproval ? 'Owner perlu memproses approval.' : 'Ajukan persetujuan sebelum barang diterima.')), 'state' => $hasApprovedApproval ? 'done' : ($hasPendingApproval || $hasRejectedApproval ? 'current' : 'pending')],
                ['title' => ($purchase->status === 'received' ? 'Barang diterima penuh' : ($purchase->status === 'partial_received' ? 'Barang diterima sebagian' : 'Menunggu barang')), 'text' => 'Terima barang penuh atau sebagian sesuai fisik yang datang.', 'state' => $purchase->status === 'received' ? 'done' : ($purchase->status === 'partial_received' ? 'current' : 'pending')],
                ['title' => ($purchase->payment_status === 'paid' ? 'Pembayaran lunas' : 'Hutang berjalan'), 'text' => 'Catat pembayaran, bukti transfer, dan rekonsiliasi invoice supplier.', 'state' => $purchase->payment_status === 'paid' ? 'done' : ((float) $purchase->remaining_amount > 0 ? 'current' : 'pending')],
            ];
        @endphp

        <div class="po-kpi-grid">
            <div class="po-kpi po-kpi-total"><span>Total</span><strong>Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</strong></div>
            <div class="po-kpi"><span>Sudah Bayar</span><strong>Rp {{ number_format((float) $purchase->paid_amount, 0, ',', '.') }}</strong></div>
            <div class="po-kpi po-kpi-remain"><span>Sisa</span><strong>Rp {{ number_format((float) $purchase->remaining_amount, 0, ',', '.') }}</strong></div>
        </div>

        <div class="po-card">
            <div class="po-card-head"><h3 class="po-card-title">Rekonsiliasi Hutang</h3></div>
            <div class="po-card-body">
                <div class="po-info-grid">
                    <div class="po-info"><span>Barang</span><strong>Order {{ number_format($orderedQty, 0, ',', '.') }} / Terima {{ number_format($receivedQty, 0, ',', '.') }} / Retur {{ number_format($returnedQty, 0, ',', '.') }}</strong></div>
                    <div class="po-info"><span>Dokumen</span><strong>Invoice {{ $purchase->supplier_invoice_number ?: '-' }} / SJ {{ $purchase->delivery_note_number ?: '-' }}</strong></div>
                    <div class="po-info"><span>Potongan Retur</span><strong>Rp {{ number_format($returnAmount, 0, ',', '.') }}</strong></div>
                    <div class="po-info"><span>Rumus Hutang</span><strong>Total Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }} - Bayar Rp {{ number_format((float) $purchase->paid_amount, 0, ',', '.') }} = Sisa Rp {{ number_format((float) $purchase->remaining_amount, 0, ',', '.') }}</strong></div>
                    <div class="po-info"><span>Invoice Supplier</span><strong>{{ $invoiceAmount === null ? '-' : 'Rp '.number_format($invoiceAmount, 0, ',', '.') }}</strong></div>
                    <div class="po-info"><span>Status Rekonsiliasi</span><strong>{{ $reconciliationLabel }}{{ $invoiceDifference === null ? '' : ' / Selisih Rp '.number_format($invoiceDifference, 0, ',', '.') }}</strong></div>
                </div>
                <form class="po-recon-form" method="POST" action="{{ route('admin.supplier-purchases.reconcile', $purchase) }}">
                    @csrf
                    <div class="po-form-grid">
                        <label><span class="po-label">Total Invoice Supplier</span><input class="po-input" type="number" min="0" step="0.01" name="supplier_invoice_amount" value="{{ $purchase->supplier_invoice_amount }}"></label>
                        <label><span class="po-label">Status</span><select class="po-select" name="reconciliation_status"><option value="unchecked" @selected(($purchase->reconciliation_status ?? 'unchecked') === 'unchecked')>Belum Dicek</option><option value="matched" @selected(($purchase->reconciliation_status ?? '') === 'matched')>Cocok</option><option value="mismatch" @selected(($purchase->reconciliation_status ?? '') === 'mismatch')>Selisih</option><option value="dispute" @selected(($purchase->reconciliation_status ?? '') === 'dispute')>Dispute</option></select></label>
                    </div>
                    <textarea class="po-textarea" name="reconciliation_note" placeholder="Catatan selisih invoice / dispute">{{ $purchase->reconciliation_note }}</textarea>
                    <button class="po-btn po-btn-primary" type="submit">Simpan Rekonsiliasi</button>
                </form>
            </div>
        </div>

        <div class="po-detail-columns">
            <div class="po-main-stack">
                <div class="po-card">
                    <div class="po-card-head"><h3 class="po-card-title">Detail Barang Dipesan</h3><span class="po-chip po-chip-draft">{{ number_format((int) ($purchase->items_count ?? $purchase->items->count()), 0, ',', '.') }} barang</span></div>
                    <div class="po-card-body">
                        <div class="po-table-wrap">
                            <table class="po-table">
                                <thead><tr><th>Produk</th><th class="po-money">Qty</th><th class="po-money">Diterima</th><th class="po-money">Retur</th><th class="po-money">Harga Beli</th><th class="po-money">Ongkir</th><th class="po-money">HPP Final</th></tr></thead>
                                <tbody>
                                    @forelse($purchase->items as $item)
                                        <tr>
                                            <td><strong>{{ $item->product_name }}</strong></td>
                                            <td class="po-money">{{ number_format((int) $item->quantity, 0, ',', '.') }}</td>
                                            <td class="po-money">{{ number_format((int) ($item->received_quantity ?? 0), 0, ',', '.') }}</td>
                                            <td class="po-money">{{ number_format((int) ($item->returned_quantity ?? 0), 0, ',', '.') }}</td>
                                            <td class="po-money">Rp {{ number_format((float) $item->unit_cost, 0, ',', '.') }}</td>
                                            <td class="po-money">Rp {{ number_format((float) ($item->shipping_allocation_amount ?? 0), 0, ',', '.') }}</td>
                                            <td class="po-money"><strong>Rp {{ number_format((float) (($item->landed_line_total ?? 0) > 0 ? $item->landed_line_total : $item->line_total), 0, ',', '.') }}</strong><br><span style="color:#64748b;font-size:.72rem;">/unit Rp {{ number_format((float) (($item->landed_unit_cost ?? 0) > 0 ? $item->landed_unit_cost : $item->unit_cost), 0, ',', '.') }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7">Belum ada barang.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if(in_array((string) $purchase->status, ['draft', 'partial_received'], true))
                            <form method="POST" action="{{ route('admin.supplier-purchases.receive-partial', $purchase) }}" class="po-attachment-form" style="margin-top:1rem;">
                                @csrf
                                <div class="po-card-head" style="border:0; border-radius:10px;"><h3 class="po-card-title">Terima Sebagian</h3></div>
                                <div class="po-table-wrap">
                                    <table class="po-table">
                                        <thead><tr><th>Produk</th><th class="po-money">Sisa</th><th class="po-money">Qty Terima</th></tr></thead>
                                        <tbody>
                                            @foreach($purchase->items as $item)
                                                @php $remainingReceive = max((int) $item->quantity - (int) ($item->received_quantity ?? 0), 0); @endphp
                                                @if($remainingReceive > 0)
                                                    <tr><td>{{ $item->product_name }}</td><td class="po-money">{{ number_format($remainingReceive, 0, ',', '.') }}</td><td class="po-money"><input class="po-input" type="number" min="0" max="{{ $remainingReceive }}" name="received[{{ $item->id }}]" value="0"></td></tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <textarea class="po-textarea" name="note" placeholder="Catatan penerimaan sebagian (opsional)"></textarea>
                                <button class="po-btn po-btn-primary" type="submit">Simpan Penerimaan Sebagian</button>
                            </form>
                        @endif

                        @if(in_array((string) $purchase->status, ['partial_received', 'received'], true))
                            <form method="POST" action="{{ route('admin.supplier-purchases.returns.store', $purchase) }}" class="po-attachment-form" style="margin-top:1rem;">
                                @csrf
                                <div class="po-card-head" style="border:0; border-radius:10px;"><h3 class="po-card-title">Retur Pembelian</h3></div>
                                <div class="po-form-grid">
                                    <label><span class="po-label">Barang</span><select class="po-select" name="supplier_purchase_item_id" required>@foreach($purchase->items as $item) @php $returnableQty = max((int) ($item->received_quantity ?? 0) - (int) ($item->returned_quantity ?? 0), 0); @endphp @if($returnableQty > 0)<option value="{{ $item->id }}">{{ $item->product_name }} - bisa retur {{ number_format($returnableQty, 0, ',', '.') }}</option>@endif @endforeach</select></label>
                                    <label><span class="po-label">Qty Retur</span><input class="po-input" type="number" min="1" name="quantity" value="1" required></label>
                                </div>
                                <label><span class="po-label">Alasan</span><input class="po-input" name="reason" placeholder="Rusak / kurang / kadaluarsa" required></label>
                                <textarea class="po-textarea" name="note" placeholder="Catatan retur (opsional)"></textarea>
                                <button class="po-btn po-btn-danger" type="submit" onclick="return confirm('Catat retur dan kurangi hutang supplier?')">Catat Retur</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="po-card">
                    <div class="po-card-head"><h3 class="po-card-title">Riwayat Approval</h3></div>
                    <div class="po-card-body"><div class="po-log">
                        @forelse($approvalHistory as $approval)
                            <div class="po-log-row">
                                <div><strong>#{{ $approval['id'] }} {{ ($approvalStatusLabels ?? [])[(string) $approval['status']] ?? ucfirst((string) $approval['status']) }}</strong> {{ $approval['is_current'] ? '- versi draft saat ini' : '- versi lama' }}</div>
                                <div>Diajukan: {{ $approval['requester'] }}{{ $approval['requested_at'] ? ' pada '.$approval['requested_at'] : '' }}</div>
                                @if($approval['reviewed_at'])<div>Diproses: {{ $approval['reviewer'] }} pada {{ $approval['reviewed_at'] }}</div>@endif
                                @if($approval['review_note'])<div>Catatan: {{ $approval['review_note'] }}</div>@endif
                            </div>
                        @empty
                            <div class="po-note">Belum ada approval untuk pembelian ini.</div>
                        @endforelse
                    </div></div>
                </div>

                <div class="po-card">
                    <div class="po-card-head"><h3 class="po-card-title">Riwayat Retur Pembelian</h3><a class="po-btn" href="{{ route('admin.supplier-purchases.returns.pdf', $purchase) }}">Cetak Nota Retur</a></div>
                    <div class="po-card-body"><div class="po-log">
                        @forelse($purchase->returns as $return)
                            <div class="po-log-row">
                                <div><strong>{{ $return->item?->product_name ?? '-' }}</strong> x {{ number_format((int) $return->quantity, 0, ',', '.') }}</div>
                                <div>Potong hutang: Rp {{ number_format((float) $return->amount, 0, ',', '.') }}</div>
                                <div>Alasan: {{ $return->reason }} - oleh {{ $return->creator?->name ?? '-' }} - {{ optional($return->created_at)->format('d/m/Y H:i') }}</div>
                                @if($return->note)<div>Catatan: {{ $return->note }}</div>@endif
                            </div>
                        @empty
                            <div class="po-note">Belum ada retur untuk pembelian ini.</div>
                        @endforelse
                    </div></div>
                </div>

                <div class="po-card">
                    <div class="po-card-head"><h3 class="po-card-title">Audit Trail Pembelian</h3></div>
                    <div class="po-card-body"><div class="po-log">
                        @forelse(($auditLogs ?? collect()) as $log)
                            <div class="po-log-row">
                                <span class="po-log-action">{{ ($auditActionLabels ?? [])[(string) $log->action] ?? str_replace('_', ' ', (string) $log->action) }}</span>
                                <div><strong>{{ optional($log->created_at)->format('d/m/Y H:i') }}</strong> oleh {{ $log->user?->name ?? '-' }}</div>
                                @php $context = (array) ($log->context ?? []); @endphp
                                @if(! empty($context['number']))<div>Nomor: {{ $context['number'] }}</div>@endif
                                @if(isset($context['total_amount']) || isset($context['payment_amount']) || isset($context['remaining_amount']))
                                    <div>@if(isset($context['total_amount'])) Total: Rp {{ number_format((float) $context['total_amount'], 0, ',', '.') }} @endif @if(isset($context['payment_amount'])) Bayar: Rp {{ number_format((float) $context['payment_amount'], 0, ',', '.') }} @endif @if(isset($context['remaining_amount'])) Sisa: Rp {{ number_format((float) $context['remaining_amount'], 0, ',', '.') }} @endif</div>
                                @endif
                            </div>
                        @empty
                            <div class="po-note">Belum ada audit trail untuk pembelian ini.</div>
                        @endforelse
                    </div></div>
                </div>
            </div>

            <div class="po-side-stack">
                <div class="po-card">
                    <div class="po-card-head"><h3 class="po-card-title">Timeline PO</h3></div>
                    <div class="po-card-body">
                        <div class="po-timeline">
                            @foreach($timelineSteps as $step)
                                <div class="po-timeline-step {{ $step['state'] === 'done' ? 'is-done' : ($step['state'] === 'current' ? 'is-current' : '') }}">
                                    <span class="po-timeline-dot">{{ $step['state'] === 'done' ? 'OK' : $loop->iteration }}</span>
                                    <div>
                                        <div class="po-timeline-title">{{ $step['title'] }}</div>
                                        <div class="po-timeline-text">{{ $step['text'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="po-card">
                    <div class="po-card-head"><h3 class="po-card-title">Informasi PO</h3></div>
                    <div class="po-card-body"><div class="po-info-grid">
                        <div class="po-info"><span>Status Barang</span><strong><span class="po-chip po-chip-{{ $purchase->status }}">{{ ($purchaseStatusLabels ?? [])[(string) $purchase->status] ?? ucfirst((string) $purchase->status) }}</span></strong></div>
                        <div class="po-info"><span>Status Bayar</span><strong><span class="po-chip po-chip-{{ $purchase->payment_status }}">{{ ($paymentStatusLabels ?? [])[(string) $purchase->payment_status] ?? ucfirst((string) $purchase->payment_status) }}</span></strong></div>
                        <div class="po-info"><span>Tanggal Order</span><strong>{{ optional($purchase->ordered_at)->format('d/m/Y') ?: '-' }}</strong></div>
                        <div class="po-info"><span>Jatuh Tempo</span><strong>{{ optional($purchase->due_date)->format('d/m/Y') ?: '-' }}</strong></div>
                        <div class="po-info"><span>Termin</span><strong>{{ number_format((int) ($purchase->payment_term_days ?? 0), 0, ',', '.') }} hari</strong></div>
                        <div class="po-info"><span>Perlakuan Ongkir</span><strong>{{ ($purchase->shipping_accounting_treatment ?? 'inventory') === 'inventory' ? 'Masuk HPP Persediaan' : 'Beban Ongkir Pembelian' }}</strong></div>
                        <div class="po-info"><span>Dibuat Oleh</span><strong>{{ $purchase->creator?->name ?? '-' }}</strong></div>
                        <div class="po-info"><span>No Invoice Supplier</span><strong>{{ $purchase->supplier_invoice_number ?: '-' }}</strong></div>
                        <div class="po-info"><span>No Surat Jalan</span><strong>{{ $purchase->delivery_note_number ?: '-' }}</strong></div>
                    </div></div>
                </div>

                <div class="po-card">
                    <div class="po-card-head"><h3 class="po-card-title">Lampiran Dokumen</h3><span class="po-chip po-chip-draft">{{ number_format((int) ($purchase->attachments_count ?? $purchase->attachments->count()), 0, ',', '.') }} file</span></div>
                    <div class="po-card-body">
                        <form method="POST" action="{{ route('admin.supplier-purchases.attachments.store', $purchase) }}" enctype="multipart/form-data" class="po-attachment-form">
                            @csrf
                            <div class="po-form-grid">
                                <label><span class="po-label">Jenis</span><select class="po-select" name="kind" required><option value="supplier_invoice">Invoice Supplier</option><option value="delivery_note">Surat Jalan</option><option value="payment_proof">Bukti Transfer</option><option value="received_photo">Foto Barang</option><option value="other">Lainnya</option></select></label>
                                <label><span class="po-label">File</span><input class="po-input" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf" required><div class="po-file-help">Format: JPG, PNG, WEBP, PDF. Maksimal 5 MB.</div></label>
                            </div>
                            <label><span class="po-label">Catatan</span><textarea class="po-textarea" name="note" placeholder="Opsional, contoh: invoice asli dari supplier"></textarea></label>
                            <button class="po-btn po-btn-primary" type="submit">Upload Lampiran</button>
                        </form>
                        <div class="po-attachment-list">
                            @forelse($purchase->attachments as $attachment)
                                @php $kindLabel = match((string) $attachment->kind) { 'supplier_invoice' => 'Invoice Supplier', 'delivery_note' => 'Surat Jalan', 'payment_proof' => 'Bukti Transfer', 'received_photo' => 'Foto Barang', default => 'Lainnya' }; @endphp
                                <div class="po-attachment-row"><div><a href="{{ route('admin.supplier-purchase-attachments.download', $attachment) }}" target="_blank" rel="noopener">{{ $attachment->original_name ?: basename((string) $attachment->path) }}</a><div class="po-attachment-meta">{{ $kindLabel }} - {{ number_format(((int) $attachment->size) / 1024, 1, ',', '.') }} KB - oleh {{ $attachment->uploader?->name ?? '-' }}</div>@if($attachment->note)<div class="po-attachment-meta">Catatan: {{ $attachment->note }}</div>@endif</div><form method="POST" action="{{ route('admin.supplier-purchase-attachments.destroy', $attachment) }}">@csrf @method('DELETE')<button class="po-btn po-btn-danger" type="submit" onclick="return confirm('Hapus lampiran ini?')">Hapus</button></form></div>
                            @empty
                                <div class="po-note">Belum ada lampiran. Upload invoice, surat jalan, bukti transfer, atau foto barang di sini.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="po-card">
                    <div class="po-card-head"><h3 class="po-card-title">Rincian Nilai</h3></div>
                    <div class="po-card-body"><div class="po-table-wrap"><table class="po-table"><tbody>
                        <tr><td>Subtotal</td><td class="po-money">Rp {{ number_format((float) $purchase->subtotal, 0, ',', '.') }}</td></tr>
                        <tr><td>Diskon</td><td class="po-money">Rp {{ number_format((float) $purchase->discount_amount, 0, ',', '.') }}</td></tr>
                        <tr><td>Pajak</td><td class="po-money">Rp {{ number_format((float) $purchase->tax_amount, 0, ',', '.') }}</td></tr>
                        <tr><td>Ongkir</td><td class="po-money">Rp {{ number_format((float) $purchase->shipping_amount, 0, ',', '.') }}</td></tr>
                        <tr><td>Ongkir Masuk HPP</td><td class="po-money">Rp {{ number_format((float) ($purchase->inventory_shipping_amount ?? 0), 0, ',', '.') }}</td></tr>
                        <tr><td>Beban Ongkir</td><td class="po-money">Rp {{ number_format((float) ($purchase->expense_shipping_amount ?? 0), 0, ',', '.') }}</td></tr>
                        <tr><td><strong>Total</strong></td><td class="po-money"><strong>Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</strong></td></tr>
                    </tbody></table></div></div>
                </div>

                <div class="po-card">
                    <div class="po-card-head"><h3 class="po-card-title">Riwayat Pembayaran & Catatan</h3></div>
                    <div class="po-card-body"><div class="po-log">
                        @forelse($purchase->payments as $payment)
                            <div class="po-log-row">
                                <div><strong>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</strong> via {{ $payment->payment_method }}</div>
                                @if(! empty($payment->reference_number))<div>Referensi: {{ $payment->reference_number }}</div>@endif
                                <div>{{ optional($payment->paid_at)->format('d/m/Y H:i') }} oleh {{ $payment->receiver?->name ?? '-' }}</div>
                                @if($payment->note)<div>Catatan: {{ $payment->note }}</div>@endif
                                @if($payment->proofAttachments->isNotEmpty())<div class="po-proof-list">@foreach($payment->proofAttachments as $proof)<a class="po-proof-link" href="{{ route('admin.supplier-purchase-attachments.download', $proof) }}" target="_blank" rel="noopener">Bukti: {{ $proof->original_name ?: basename((string) $proof->path) }}</a>@endforeach</div>@endif
                                <form method="POST" action="{{ route('admin.supplier-purchase-payments.proof.store', $payment) }}" enctype="multipart/form-data" class="po-proof-form">@csrf<label><span class="po-label">Upload Bukti Bayar</span><input class="po-input" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf" required></label><button class="po-btn po-btn-primary" type="submit">Upload</button></form>
                            </div>
                        @empty
                            <div class="po-note">Belum ada pembayaran dicatat.</div>
                        @endforelse
                    </div>@if($purchase->note)<div class="po-note" style="margin-top:.75rem;">{{ $purchase->note }}</div>@endif</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
