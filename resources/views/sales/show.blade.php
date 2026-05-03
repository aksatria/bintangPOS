<x-app-layout>
    @php
        $canSalesCorrection = auth()->user()?->hasPermission('sales.correction.manage') ?? false;
        $canSalesPendingSettle = auth()->user()?->hasPermission('sales.pending.settle') ?? false;
    @endphp
    <x-slot name="header">
        <div class="sales-header">
            <div>
                <p class="sales-kicker">Transaksi</p>
                <h2 class="sales-title">Detail Penjualan</h2>
            </div>
            <p class="sales-header-note">Gunakan cetak ulang struk dengan alasan agar audit tetap rapi.</p>
        </div>
    </x-slot>

    <style>
        .sales-page {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #dbe4f0;
            --bg-soft: #f8fbff;
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 1rem 2rem;
        }
        @media (min-width: 1024px) {
            .sales-page { padding: 0 1.5rem 2.25rem; }
        }
        .sales-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .sales-kicker {
            margin: 0;
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #3b82f6;
        }
        .sales-title {
            margin: .2rem 0 0;
            color: var(--ink);
            font-size: 2rem;
            line-height: 1.1;
            font-weight: 800;
        }
        .sales-header-note {
            margin: 0;
            color: var(--muted);
            font-size: .92rem;
            max-width: 35rem;
            text-align: right;
        }
        .sales-card {
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, .05);
            overflow: hidden;
        }
        .sales-card-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .85rem;
            padding: .95rem 1rem;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, #f8fbff 0%, #fff 100%);
        }
        .sales-card--rose .sales-card-head {
            background: linear-gradient(180deg, #fff1f2 0%, #ffffff 100%);
            border-bottom-color: #fecdd3;
        }
        .sales-card--amber .sales-card-head {
            background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
            border-bottom-color: #fde68a;
        }
        .sales-card--mint .sales-card-head {
            background: linear-gradient(180deg, #ecfeff 0%, #ffffff 100%);
            border-bottom-color: #bae6fd;
        }
        .sales-card-title {
            margin: 0;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: .07em;
            font-size: .86rem;
            font-weight: 700;
        }
        .sales-card-note {
            margin: 0;
            color: var(--muted);
            font-size: .82rem;
        }
        .sales-card-body { padding: 1rem; }

        .sales-meta-grid {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        @media (min-width: 760px) {
            .sales-meta-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        .sales-meta {
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 12px;
            padding: .72rem .8rem;
        }
        .sales-meta-label {
            margin: 0;
            color: var(--muted);
            font-size: .76rem;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .sales-meta-value {
            margin: .22rem 0 0;
            color: var(--ink);
            font-size: 1.02rem;
            font-weight: 700;
            line-height: 1.25;
            word-break: break-word;
        }
        .sales-status {
            display: inline-flex;
            align-items: center;
            padding: .2rem .58rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .03em;
        }
        .sales-status--paid {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
        }
        .sales-status--pending {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #b45309;
        }
        .sales-status--cancelled {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }
        .sales-inline-actions {
            display: inline-flex;
            gap: .4rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .sales-copy-btn {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 8px;
            font-size: .7rem;
            font-weight: 700;
            padding: .2rem .48rem;
            line-height: 1.1;
        }
        .sales-qris-ref {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .2rem .52rem;
            border-radius: 999px;
            border: 1px solid #93c5fd;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: .74rem;
            font-weight: 700;
            line-height: 1.2;
            margin-top: .35rem;
            flex-wrap: wrap;
        }
        .sales-qty-badge {
            display: inline-flex;
            align-items: center;
            margin-left: .45rem;
            padding: .08rem .45rem;
            border-radius: 999px;
            background: #e8f1ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            font-size: .74rem;
            font-weight: 700;
            line-height: 1.2;
            vertical-align: middle;
        }
        .sales-money {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum" 1;
        }
        .sales-total-grid {
            margin-top: .95rem;
            display: grid;
            gap: .65rem;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        @media (min-width: 700px) {
            .sales-total-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .sales-total-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        .sales-total {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: .7rem .82rem;
        }
        .sales-total--sub { background: #f8fafc; }
        .sales-total--main { background: #eff6ff; border-color: #bfdbfe; }
        .sales-total--paid { background: #ecfdf5; border-color: #bbf7d0; }
        .sales-total--change { background: #ecfeff; border-color: #bae6fd; }
        .sales-total-label {
            margin: 0;
            color: #475569;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 700;
        }
        .sales-total-value {
            margin: .16rem 0 0;
            color: var(--ink);
            font-size: 1.85rem;
            line-height: 1.05;
            font-weight: 700;
        }

        .sales-main {
            margin-top: .95rem;
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr);
        }
        @media (min-width: 1200px) {
            .sales-main {
                grid-template-columns: minmax(0, 1.35fr) minmax(520px, 1fr);
                align-items: start;
            }
        }
        .sales-stack { display: grid; gap: 1rem; }
        .sales-form-grid {
            display: grid;
            gap: .7rem;
            grid-template-columns: minmax(0, 1fr);
        }
        @media (min-width: 900px) {
            .sales-form-grid.reprint { grid-template-columns: 1.5fr .8fr auto; align-items: end; }
            .sales-form-grid.pending { grid-template-columns: 1fr 1fr auto; align-items: end; }
        }
        .sales-refund-grid {
            display: grid;
            gap: .9rem;
            grid-template-columns: minmax(0, 1fr);
        }
        .sales-list {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            padding: .35rem;
            max-height: 16rem;
            overflow: auto;
            overflow-x: hidden;
        }
        .sales-refund-filter-wrap {
            margin-bottom: .5rem;
        }
        .sales-refund-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .sales-refund-table th,
        .sales-refund-table td {
            padding: .5rem .45rem;
            border-bottom: 1px solid #e6edf7;
            vertical-align: middle;
            text-align: left;
        }
        .sales-refund-table th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            font-weight: 800;
            background: #f8fafc;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .sales-refund-table th:nth-child(1),
        .sales-refund-table td:nth-child(1) { width: 44%; }
        .sales-refund-table th:nth-child(2),
        .sales-refund-table td:nth-child(2) { width: 12%; }
        .sales-refund-table th:nth-child(3),
        .sales-refund-table td:nth-child(3) { width: 26%; }
        .sales-refund-table th:nth-child(4),
        .sales-refund-table td:nth-child(4) { width: 18%; }
        .sales-refund-table td:nth-child(2),
        .sales-refund-table td:nth-child(3),
        .sales-refund-table td:nth-child(4) { white-space: nowrap; }
        .sales-refund-check {
            margin-right: .35rem;
            margin-top: .05rem;
        }
        .sales-refund-item {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }
        .sales-refund-name {
            margin: 0;
            color: #0f172a;
            font-size: .86rem;
            font-weight: 700;
            line-height: 1.25;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .sales-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            color: #64748b;
            padding: .9rem;
            font-size: .88rem;
            text-align: center;
        }
        .sales-list-row {
            border: 1px solid #e5edf7;
            border-radius: 10px;
            padding: .5rem .55rem;
            display: grid;
            grid-template-columns: 20px minmax(0, 1fr) minmax(130px, auto) 92px;
            align-items: center;
            gap: .55rem;
        }
        @media (min-width: 1200px) {
            .sales-list-row {
                grid-template-columns: 20px minmax(280px, 1fr) minmax(140px, auto) 92px;
            }
        }
        @media (max-width: 900px) {
            .sales-list-row {
                grid-template-columns: 20px minmax(0, 1fr);
                gap: .45rem .55rem;
            }
            .sales-list-row-price {
                grid-column: 2 / 3;
                text-align: left;
            }
            .sales-list-row .refund-qty {
                grid-column: 2 / 3;
                max-width: 130px;
            }
        }
        .sales-list-row-name {
            margin: 0;
            font-size: .9rem;
            color: #0f172a;
            font-weight: 700;
            line-height: 1.2;
            overflow-wrap: break-word;
            word-break: normal;
            white-space: normal;
            max-width: 100%;
        }
        .sales-list-row-note {
            margin: .15rem 0 0;
            font-size: .78rem;
            color: #64748b;
        }
        .sales-list-row-price {
            text-align: right;
            font-size: .85rem;
            color: #334155;
            font-weight: 700;
            white-space: normal;
            overflow-wrap: anywhere;
        }
        .sales-list-row input[type="number"] { min-width: 0; width: 100%; }

        .sales-table-wrap {
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
        }
        .sales-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .sales-table th,
        .sales-table td {
            padding: .72rem .78rem;
            border-bottom: 1px solid #e7edf6;
            vertical-align: top;
        }
        .sales-table th {
            background: #f8fbff;
            color: #475569;
            font-size: .74rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            font-weight: 700;
            text-align: left;
        }
        .sales-table td { color: #0f172a; font-size: .95rem; }
        .sales-table td:nth-child(1) { min-width: 0; }
        .sales-table td:nth-child(2) { min-width: 0; white-space: nowrap; text-align: left; }
        .sales-table th:nth-child(1),
        .sales-table td:nth-child(1) {
            width: 64%;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .sales-table th:nth-child(2),
        .sales-table td:nth-child(2) { width: 36%; }
        @media (max-width: 640px) {
            .sales-table th, .sales-table td { padding: .58rem .5rem; font-size: .86rem; }
        }
        .sales-sticky-actions {
            position: sticky;
            bottom: .85rem;
            z-index: 20;
            display: flex;
            justify-content: flex-end;
            margin-top: .35rem;
            pointer-events: none;
        }
        .sales-sticky-actions-inner {
            pointer-events: auto;
            display: inline-flex;
            gap: .45rem;
            align-items: center;
            flex-wrap: wrap;
            background: rgba(15, 23, 42, .92);
            color: #e2e8f0;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 999px;
            padding: .45rem .55rem;
            box-shadow: 0 14px 26px rgba(2, 6, 23, .3);
        }
        .sales-sticky-link {
            color: #e2e8f0;
            text-decoration: none;
            font-size: .74rem;
            font-weight: 700;
            border: 1px solid rgba(148, 163, 184, .5);
            border-radius: 999px;
            padding: .24rem .55rem;
            background: rgba(30, 41, 59, .9);
        }
        .sales-sticky-link:hover {
            background: rgba(59, 130, 246, .28);
            border-color: rgba(96, 165, 250, .8);
        }
        .sales-toast-wrap {
            position: fixed;
            right: 1rem;
            top: 1rem;
            z-index: 70;
            display: grid;
            gap: .45rem;
        }
        .sales-toast {
            min-width: 230px;
            max-width: 360px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            box-shadow: 0 12px 24px rgba(15, 23, 42, .16);
            padding: .55rem .7rem;
            font-size: .82rem;
            font-weight: 600;
        }
        .sales-toast--success { border-color: #86efac; background: #f0fdf4; color: #166534; }
        .sales-toast--error { border-color: #fca5a5; background: #fef2f2; color: #991b1b; }
        .sales-confirm-backdrop {
            position: fixed;
            inset: 0;
            z-index: 75;
            background: rgba(15, 23, 42, .48);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .sales-confirm-backdrop.is-open { display: flex; }
        .sales-confirm-box {
            width: min(100%, 420px);
            background: #fff;
            border-radius: 14px;
            border: 1px solid #dbe4f0;
            box-shadow: 0 22px 46px rgba(15, 23, 42, .3);
            overflow: hidden;
        }
        .sales-confirm-head {
            padding: .9rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .sales-confirm-title {
            margin: 0;
            font-size: .9rem;
            font-weight: 800;
            color: #0f172a;
        }
        .sales-confirm-body {
            padding: .95rem 1rem;
            color: #334155;
            font-size: .9rem;
            line-height: 1.4;
        }
        .sales-confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: .45rem;
            padding: .85rem 1rem 1rem;
        }
        .sales-confirm-btn {
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: .42rem .68rem;
            font-size: .8rem;
            font-weight: 700;
            background: #fff;
            color: #334155;
        }
        .sales-confirm-btn--danger {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff;
        }
        .sales-debt-box {
            margin-top: .75rem;
            border: 1px solid #dbe5f2;
            border-radius: 12px;
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            padding: .72rem .82rem;
        }
        .sales-debt-box--amber {
            border-color: #fde68a;
            background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
        }
        .sales-debt-box--blue {
            border-color: #bfdbfe;
            background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
        }
        .sales-debt-title {
            margin: 0;
            font-size: .78rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            font-weight: 800;
            color: #334155;
        }
        .sales-debt-meta {
            margin-top: .35rem;
            font-size: .8rem;
            color: #334155;
            line-height: 1.4;
        }
        .sales-debt-link {
            margin-top: .35rem;
            display: inline-block;
            font-size: .76rem;
            font-weight: 700;
            text-decoration: underline;
        }
        .is-invalid-refund {
            outline: 2px solid #fca5a5;
            border-color: #ef4444 !important;
            background: #fef2f2 !important;
        }

    </style>

    <div class="page-shell sales-page space-y-4">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <div class="sales-card">
            <div class="sales-card-head">
                <h3 class="sales-card-title">Ringkasan Transaksi</h3>
                <p class="sales-card-note">Informasi inti untuk kasir dan audit</p>
            </div>
            <div class="sales-card-body">
                @php
                    $attemptLogs = is_array($sale->payment_attempt_logs ?? null) ? $sale->payment_attempt_logs : [];
                    $latestAttempt = count($attemptLogs) > 0 ? end($attemptLogs) : null;
                    $qrisRef = null;
                    $qrisIssuer = null;
                    $hasLinkedDebt = $sale->relationLoaded('customerDebts') && $sale->customerDebts->isNotEmpty();
                    $linkedDebt = $hasLinkedDebt ? $sale->customerDebts->sortByDesc('id')->first() : null;
                    $remainingDue = max((float) $sale->total_amount - (float) $sale->paid_amount, 0);
                    $paymentMethod = (string) ($sale->payment_method ?? 'cash');
                    $paymentMethodLabel = match ($paymentMethod) {
                        'e_wallet' => 'E-WALLET',
                        'mixed' => 'SPLIT',
                        'installment' => 'CICILAN',
                        default => strtoupper(str_replace('_', ' ', $paymentMethod)),
                    };
                    if ($sale->status->value === 'pending' && $paymentMethod !== 'installment' && $hasLinkedDebt) {
                        $paymentMethodLabel = 'HUTANG (PENDING)';
                    } elseif ($sale->status->value === 'pending' && $paymentMethod === 'installment') {
                        $paymentMethodLabel = 'CICILAN (PENDING)';
                    }
                    if (preg_match_all('/\[QRIS\]\s*Ref:\s*(.*?)\s*\|\s*Issuer:\s*(.*)/', (string) ($sale->note ?? ''), $qrisMatches, PREG_SET_ORDER) && count($qrisMatches) > 0) {
                        $lastQris = $qrisMatches[count($qrisMatches) - 1];
                        $qrisRef = trim((string) ($lastQris[1] ?? ''));
                        $qrisIssuer = trim((string) ($lastQris[2] ?? ''));
                    }
                @endphp
                <div class="mb-3 text-xs text-slate-500">
                    Audit hint:
                    @if($latestAttempt)
                        Attempt terakhir {{ strtoupper((string) ($latestAttempt['method'] ?? '-')) }} pada {{ $latestAttempt['at'] ?? '-' }} ({{ !empty($latestAttempt['success']) ? 'SUCCESS' : 'FAILED' }}).
                    @else
                        Belum ada attempt pembayaran tambahan.
                    @endif
                </div>
                <div class="sales-meta-grid">
                    <div class="sales-meta">
                        <p class="sales-meta-label">Invoice</p>
                        <p class="sales-meta-value">
                            <span class="sales-inline-actions">
                                <span id="saleInvoiceText">{{ $sale->invoice_number }}</span>
                                <button type="button" class="sales-copy-btn" id="copyInvoiceBtn" data-invoice="{{ $sale->invoice_number }}">Copy</button>
                            </span>
                        </p>
                    </div>
                    <div class="sales-meta">
                        <p class="sales-meta-label">Tanggal</p>
                        <p class="sales-meta-value">{{ $sale->sold_at?->format('d M Y H:i') }}</p>
                    </div>
                    <div class="sales-meta">
                        <p class="sales-meta-label">Status</p>
                        <p class="sales-meta-value">
                            <span class="sales-status sales-status--{{ $sale->status->value === 'paid' ? 'paid' : ($sale->status->value === 'pending' ? 'pending' : 'cancelled') }}">
                                {{ strtoupper($sale->status->value) }}
                            </span>
                        </p>
                    </div>
                    <div class="sales-meta">
                        <p class="sales-meta-label">Kasir</p>
                        <p class="sales-meta-value">{{ $sale->user?->name }}</p>
                    </div>
                    <div class="sales-meta">
                        <p class="sales-meta-label">Pelanggan</p>
                        <p class="sales-meta-value">{{ $sale->customer?->name ?: ($sale->customer_name ?: '-') }}</p>
                    </div>
                    <div class="sales-meta">
                        <p class="sales-meta-label">Metode Bayar</p>
                        <p class="sales-meta-value">{{ $paymentMethodLabel }}</p>
                        @if($qrisRef || $qrisIssuer)
                            <div class="sales-qris-ref">
                                <span>Ref QRIS: {{ $qrisRef ?: '-' }}</span>
                                @if($qrisIssuer)
                                    <span>|</span>
                                    <span>{{ $qrisIssuer }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="mt-3">
                    <button type="button" class="sales-copy-btn" id="copySummaryBtn">Salin Ringkasan</button>
                </div>
                <div class="sales-total-grid">
                    @if((float) $sale->subtotal !== (float) $sale->total_amount)
                        <div class="sales-total sales-total--sub">
                            <p class="sales-total-label">Subtotal</p>
                            <p class="sales-total-value sales-money">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</p>
                        </div>
                    @endif
                    <div class="sales-total sales-total--main">
                        <p class="sales-total-label">Total</p>
                        <p class="sales-total-value sales-money">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</p>
                    </div>
                    <div class="sales-total sales-total--paid">
                        <p class="sales-total-label">Dibayar</p>
                        <p class="sales-total-value sales-money">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</p>
                    </div>
                    @if($sale->status->value === 'pending')
                        <div class="sales-total sales-total--sub">
                            <p class="sales-total-label">Kurang Bayar</p>
                            <p class="sales-total-value sales-money">Rp {{ number_format($remainingDue, 0, ',', '.') }}</p>
                        </div>
                    @endif
                    <div class="sales-total sales-total--change">
                        <p class="sales-total-label">Kembalian</p>
                        <p class="sales-total-value sales-money">Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</p>
                    </div>
                </div>
                @if($linkedDebt)
                    <div class="sales-debt-box sales-debt-box--amber">
                        <p class="sales-debt-title">Info Piutang/Cicilan</p>
                        <div class="sales-debt-meta">
                            No: {{ $linkedDebt->number }} |
                            Sisa: Rp {{ number_format((float) $linkedDebt->remaining_amount, 0, ',', '.') }} |
                            JT: {{ $linkedDebt->due_date ? $linkedDebt->due_date->format('d/m/Y') : '-' }}
                        </div>
                        <a href="{{ route('customers.debts.index', ['q' => $linkedDebt->number]) }}" class="sales-debt-link text-amber-800">Lihat di halaman Piutang</a>
                    </div>
                @endif
                @if(!empty($customerDebtSummary) && (int) ($customerDebtSummary->debt_count ?? 0) > 0)
                    <div class="sales-debt-box sales-debt-box--blue">
                        <p class="sales-debt-title">Ringkasan Piutang Pelanggan</p>
                        <div class="sales-debt-meta">
                            Aktif: {{ number_format((int) ($customerDebtSummary->debt_count ?? 0), 0, ',', '.') }} |
                            Overdue: {{ number_format((int) ($customerDebtSummary->overdue_count ?? 0), 0, ',', '.') }} |
                            Total Sisa: Rp {{ number_format((float) ($customerDebtSummary->total_remaining ?? 0), 0, ',', '.') }}
                        </div>
                        @if(!empty($customerDebtRecentPayments) && $customerDebtRecentPayments->count() > 0)
                            <div class="mt-2 text-xs">
                                <div class="font-semibold mb-1">Histori Cicilan Terbaru</div>
                                @foreach($customerDebtRecentPayments as $pay)
                                    <div>Rp {{ number_format((float) $pay->amount, 0, ',', '.') }} - {{ strtoupper(str_replace('_', ' ', (string) $pay->payment_method)) }} ({{ optional($pay->paid_at)->format('d/m/Y H:i') }})</div>
                                @endforeach
                            </div>
                        @endif
                        <a href="{{ route('customers.debts.index', ['q' => $sale->customer?->name]) }}" class="sales-debt-link text-blue-800">Lihat semua di halaman Piutang</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="sales-main">
            <div class="sales-stack">
                <div class="sales-card sales-card--mint" id="reprintSection">
                    <div class="sales-card-head">
                        <h3 class="sales-card-title">Cetak Ulang Struk</h3>
                        <p class="sales-card-note">Reprint wajib alasan</p>
                    </div>
                    <div class="sales-card-body">
                        <form method="POST" action="{{ route('sales.reprint-receipt', $sale) }}" class="sales-form-grid reprint">
                            @csrf
                            <div>
                                <label class="label-ui">Alasan Reprint</label>
                                <input type="text" name="reason" class="input-ui" minlength="5" required placeholder="Contoh: kertas printer habis / customer minta salinan">
                            </div>
                            <div>
                                <label class="label-ui">Mode</label>
                                <select name="mode" class="input-ui">
                                    <option value="pdf">Buka PDF</option>
                                    <option value="print">Direct Print</option>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="customer-btn customer-btn--secondary" id="reprintPrimaryBtn">Catat & Cetak Ulang</button>
                            </div>
                        </form>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('sales.receipt-print', ['sale' => $sale, 'paper' => '80']) }}" target="_blank" class="btn-danger-lite">Thermal 80mm</a>
                            <a href="{{ route('sales.receipt-print', ['sale' => $sale, 'paper' => '58']) }}" target="_blank" class="btn-danger-lite">Thermal 58mm</a>
                            <a href="{{ route('sales.receipt', $sale) }}" target="_blank" class="btn-danger-lite">PDF Struk</a>
                        </div>
                    </div>
                </div>

                <div class="sales-card">
                    <div class="sales-card-head">
                        <h3 class="sales-card-title">Item Penjualan</h3>
                        <p class="sales-card-note">{{ $sale->items->count() }} baris item</p>
                    </div>
                    <div class="sales-card-body">
                        <div class="sales-table-wrap">
                            <table class="sales-table">
                                <thead>
                                    <tr>
                                        <th>Produk / Qty</th>
                                        <th>Harga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($sale->items as $item)
                                        <tr>
                                            <td class="font-semibold">{{ $item->product_name }} <span class="sales-qty-badge">Qty {{ number_format((float) $item->quantity, 0, ',', '.') }}</span></td>
                                            <td class="sales-money">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2"><div class="sales-empty">Belum ada item penjualan.</div></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sales-stack">
                <div class="sales-card">
                    <div class="sales-card-head">
                        <h3 class="sales-card-title">Riwayat Koreksi Pembayaran</h3>
                        <p class="sales-card-note">Siapa ubah apa dan kapan</p>
                    </div>
                    <div class="sales-card-body">
                        @if(isset($correctionLogs) && $correctionLogs->count() > 0)
                            <div class="space-y-2 max-h-52 overflow-auto">
                                @foreach($correctionLogs as $log)
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                                        <p class="font-semibold text-slate-800">
                                            {{ strtoupper(str_replace('_', ' ', (string) $log->action)) }}
                                        </p>
                                        <p class="text-slate-600">
                                            {{ optional($log->created_at)->format('d M Y H:i:s') }}
                                            - {{ $log->user?->name ?? 'system' }}
                                        </p>
                                        <p class="text-slate-600">
                                            {{ (string) data_get($log->context, 'reason', data_get($log->context, 'message', '-')) }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="sales-empty">Belum ada riwayat koreksi pembayaran.</div>
                        @endif
                    </div>
                </div>

                @if(in_array($sale->status->value, ['paid', 'pending'], true) && $canSalesCorrection)
                    <div class="sales-card sales-card--rose" id="correctionSection">
                        <div class="sales-card-head">
                            <h3 class="sales-card-title">Aksi Koreksi Transaksi</h3>
                            <p class="sales-card-note">Gunakan saat diperlukan</p>
                        </div>
                        <div class="sales-card-body">
                            <div class="sales-refund-grid">
                                @if($sale->status->value === 'paid')
                                    <form method="POST" action="{{ route('sales.quick-refund', $sale) }}" class="space-y-2" data-approval="manager">
                                        @csrf
                                        <label class="label-ui">Alasan Refund (wajib)</label>
                                        <textarea name="reason" rows="3" class="input-ui" placeholder="Contoh: transaksi salah input item / customer batal" required minlength="5"></textarea>
                                        <input type="hidden" name="manager_approval_reason" value="">
                                        <input type="hidden" name="manager_approval_email" value="">
                                        <input type="hidden" name="manager_approval_password" value="">
                                        <button type="submit" class="customer-btn customer-btn--danger" data-confirm="Refund transaksi ini? Stok akan dikembalikan." id="refundPrimaryBtn">Refund & Kembalikan Stok</button>
                                    </form>
                                    <form method="POST" action="{{ route('sales.quick-partial-refund', $sale) }}" class="space-y-2" id="partial-refund-form" data-approval="manager">
                                        @csrf
                                        <label class="label-ui">Partial Refund (Pilih Item)</label>
                                        <div class="sales-refund-filter-wrap">
                                            <input type="text" id="refundItemFilter" class="input-ui" placeholder="Cari item partial refund...">
                                        </div>
                                        <div class="sales-list">
                                            <table class="sales-refund-table">
                                                <thead>
                                                    <tr>
                                                        <th>Item</th>
                                                        <th>Qty</th>
                                                        <th>Harga/Item</th>
                                                        <th>Qty Refund</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php($hasRefundableItem = false)
                                                    @foreach($sale->items as $item)
                                                        @if((int) $item->quantity > 0)
                                                            @php($hasRefundableItem = true)
                                                            <tr>
                                                                <td>
                                                                    <label class="sales-refund-item">
                                                                        <input type="checkbox" class="refund-check sales-refund-check" data-target="refund-qty-{{ $item->id }}">
                                                                        <span class="sales-refund-name refund-item-name">{{ $item->product_name }}</span>
                                                                    </label>
                                                                    <input type="hidden" name="items[{{ $loop->index }}][sale_item_id]" value="{{ $item->id }}">
                                                                </td>
                                                                <td>{{ number_format((float) $item->quantity, 0, ',', '.') }}</td>
                                                                <td class="sales-money">Rp {{ number_format($item->subtotal / max(1,$item->quantity), 0, ',', '.') }}/item</td>
                                                                <td>
                                                                    <input id="refund-qty-{{ $item->id }}" data-unit="{{ (float) ($item->subtotal / max(1,$item->quantity)) }}" type="number" name="items[{{ $loop->index }}][quantity]" min="0" max="{{ $item->quantity }}" value="0" class="input-ui text-xs refund-qty" disabled>
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                    @if(!$hasRefundableItem)
                                                        <tr>
                                                            <td colspan="4"><div class="sales-empty">Tidak ada item yang bisa direfund.</div></td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-sm text-slate-700">Estimasi total refund: <strong id="partial-refund-preview" class="sales-money">Rp 0</strong></div>
                                        <textarea name="reason" rows="2" class="input-ui" placeholder="Alasan partial refund" required minlength="5"></textarea>
                                        <input type="hidden" name="manager_approval_reason" value="">
                                        <input type="hidden" name="manager_approval_email" value="">
                                        <input type="hidden" name="manager_approval_password" value="">
                                        <button type="submit" class="customer-btn customer-btn--danger" data-confirm="Simpan partial refund?" id="partialRefundPrimaryBtn">Simpan Partial Refund</button>
                                    </form>
                                @endif
                                @if($sale->status->value === 'pending')
                                    <form method="POST" action="{{ route('sales.quick-void', $sale) }}" class="space-y-2" data-approval="manager">
                                        @csrf
                                        <label class="label-ui">Alasan Void (wajib)</label>
                                        <textarea name="reason" rows="3" class="input-ui" placeholder="Contoh: pending tidak jadi dilanjutkan" required minlength="5"></textarea>
                                        <input type="hidden" name="manager_approval_reason" value="">
                                        <input type="hidden" name="manager_approval_email" value="">
                                        <input type="hidden" name="manager_approval_password" value="">
                                        <button type="submit" class="customer-btn customer-btn--danger" data-confirm="Void transaksi pending ini?">Void Transaksi Pending</button>
                                    </form>
                                @endif
                                <div class="text-xs text-slate-500">
                                    Semua aksi refund/void dicatat di Audit Log beserta alasan dan pelaksana.
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif(in_array($sale->status->value, ['paid', 'pending'], true))
                    <div class="sales-card sales-card--rose"><div class="sales-card-body text-xs text-slate-500">Aksi refund/void hanya tersedia untuk owner/admin.</div></div>
                @endif

                @if($sale->status->value === 'pending' && $canSalesPendingSettle)
                    <div class="sales-card sales-card--amber" id="pendingSection">
                        <div class="sales-card-head">
                            <h3 class="sales-card-title">Pelunasan Pending</h3>
                            <p class="sales-card-note">Selesaikan transaksi pending dari POS</p>
                        </div>
                        <div class="sales-card-body">
                            <form method="POST" action="{{ route('sales.quick-settle-pending', $sale) }}" class="sales-form-grid pending">
                                @csrf
                                <div>
                                    <label class="label-ui">Metode Bayar</label>
                                    <select name="payment_method" class="input-ui" required>
                                        <option value="cash">Cash</option>
                                        <option value="qris">QRIS</option>
                                        <option value="debit">Debit</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="e_wallet">E-Wallet</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label-ui">Nominal Dibayar</label>
                                    <input type="number" name="paid_amount" min="{{ (float) $sale->total_amount }}" step="0.01" class="input-ui" value="{{ (float) $sale->total_amount }}" required>
                                </div>
                                <div>
                                    <button type="submit" class="customer-btn customer-btn--success" data-confirm="Lunasi transaksi pending ini sekarang?" id="pendingPrimaryBtn">Lunasi Pending</button>
                                </div>
                            </form>
                            @if(is_array($sale->payment_attempt_logs) && count($sale->payment_attempt_logs) > 0)
                                <div class="mt-4 border-t border-slate-200 pt-3">
                                    <p class="text-xs font-semibold text-slate-700 mb-2">Histori Attempt Pembayaran ({{ (int) $sale->payment_attempt_count }})</p>
                                    <div class="space-y-1 max-h-40 overflow-auto">
                                        @foreach(array_reverse($sale->payment_attempt_logs) as $log)
                                            <div class="text-xs">
                                                <span class="{{ !empty($log['success']) ? 'text-emerald-700' : 'text-rose-700' }}">{{ !empty($log['success']) ? 'SUCCESS' : 'FAILED' }}</span>
                                                <span class="text-slate-500"> | {{ $log['at'] ?? '-' }} | {{ $log['message'] ?? '-' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="sales-sticky-actions">
            <div class="sales-sticky-actions-inner">
                <a class="sales-sticky-link" href="#reprintSection">Cetak Ulang</a>
                @if(in_array($sale->status->value, ['paid', 'pending'], true) && $canSalesCorrection)
                    <a class="sales-sticky-link" href="#correctionSection">Koreksi</a>
                @endif
                @if($sale->status->value === 'pending' && $canSalesPendingSettle)
                    <a class="sales-sticky-link" href="#pendingSection">Lunasi</a>
                @endif
            </div>
        </div>
    </div>
    <div class="sales-toast-wrap" id="salesToastWrap"></div>
    <div class="sales-confirm-backdrop" id="salesConfirmBackdrop" role="dialog" aria-modal="true" aria-labelledby="salesConfirmTitle">
        <div class="sales-confirm-box">
            <div class="sales-confirm-head">
                <p class="sales-confirm-title" id="salesConfirmTitle">Konfirmasi Aksi</p>
            </div>
            <div class="sales-confirm-body" id="salesConfirmMessage">Lanjutkan aksi ini?</div>
            <div class="sales-confirm-actions">
                <button type="button" class="sales-confirm-btn" id="salesConfirmCancel">Batal</button>
                <button type="button" class="sales-confirm-btn sales-confirm-btn--danger" id="salesConfirmOk">Lanjutkan</button>
            </div>
        </div>
    </div>
    <x-approval-modal
        id="sales-approval-modal"
        title="Approval Koreksi Transaksi"
        note="Refund/Void membutuhkan persetujuan manager untuk pencatatan audit."
        reason-id="sales-approval-reason"
        email-id="sales-approval-email"
        password-id="sales-approval-password"
        error-id="sales-approval-error"
        cancel-id="sales-approval-cancel"
        submit-id="sales-approval-submit"
        reason-label="Alasan Koreksi"
        email-label="Email Manager"
        password-label="Password Manager"
    />

    @if(request()->boolean('autoprint') || request()->filled('clear_hold_id') || request()->boolean('open_receipt_pdf'))
    <script>
        window.addEventListener('load', function () {
            const clearHoldId = @json((string) request()->query('clear_hold_id', ''));
            if (clearHoldId) {
                try {
                    const key = 'pos_holds_v2';
                    const raw = localStorage.getItem(key);
                    const parsed = raw ? JSON.parse(raw) : [];
                    const next = Array.isArray(parsed) ? parsed.filter(h => h.id !== clearHoldId) : [];
                    localStorage.setItem(key, JSON.stringify(next));
                } catch (e) {}
            }

            const shouldOpenReceiptPdf = @json(request()->boolean('open_receipt_pdf'));
            if (shouldOpenReceiptPdf) {
                window.open(@json(route('sales.receipt', $sale)), '_blank');
            }

            const shouldAutoPrint = @json(request()->boolean('autoprint'));
            if (shouldAutoPrint) {
                window.open(@json(route('sales.receipt-print', $sale)), '_blank');
            }

            // Bersihkan query trigger agar pop-up tidak terulang saat reload.
            try {
                const url = new URL(window.location.href);
                url.searchParams.delete('autoprint');
                url.searchParams.delete('open_receipt_pdf');
                url.searchParams.delete('clear_hold_id');
                window.history.replaceState({}, document.title, url.toString());
            } catch (e) {}
        });
    </script>
    @endif
</x-app-layout>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checks = document.querySelectorAll('.refund-check');
    const qtyInputs = document.querySelectorAll('.refund-qty');
    const preview = document.getElementById('partial-refund-preview');
    const toMoney = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    const toastWrap = document.getElementById('salesToastWrap');
    const confirmBackdrop = document.getElementById('salesConfirmBackdrop');
    const confirmMessage = document.getElementById('salesConfirmMessage');
    const confirmCancel = document.getElementById('salesConfirmCancel');
    const confirmOk = document.getElementById('salesConfirmOk');
    const approvalModal = document.getElementById('sales-approval-modal');
    const approvalReason = document.getElementById('sales-approval-reason');
    const approvalEmail = document.getElementById('sales-approval-email');
    const approvalPassword = document.getElementById('sales-approval-password');
    const approvalError = document.getElementById('sales-approval-error');
    const approvalCancel = document.getElementById('sales-approval-cancel');
    const approvalSubmit = document.getElementById('sales-approval-submit');
    let approvalResolver = null;
    let approvalForm = null;
    let confirmResolver = null;

    const pushToast = (type, msg) => {
        if (!toastWrap) return;
        const t = document.createElement('div');
        t.className = `sales-toast sales-toast--${type}`;
        t.textContent = msg;
        toastWrap.appendChild(t);
        setTimeout(() => t.remove(), 2600);
    };
    const askConfirm = (message) => new Promise((resolve) => {
        confirmResolver = resolve;
        if (confirmMessage) confirmMessage.textContent = message || 'Lanjutkan aksi ini?';
        confirmBackdrop?.classList.add('is-open');
        confirmOk?.focus();
    });
    const closeConfirm = (result) => {
        confirmBackdrop?.classList.remove('is-open');
        if (confirmResolver) confirmResolver(Boolean(result));
        confirmResolver = null;
    };
    const askApproval = () => new Promise((resolve) => {
        approvalResolver = resolve;
        approvalModal?.classList.add('is-open');
        approvalModal?.setAttribute('aria-hidden', 'false');
        if (approvalError) approvalError.style.display = 'none';
        if (approvalReason) approvalReason.value = '';
        if (approvalEmail) approvalEmail.value = '';
        if (approvalPassword) approvalPassword.value = '';
        approvalReason?.focus();
    });
    const closeApproval = (payload) => {
        approvalModal?.classList.remove('is-open');
        approvalModal?.setAttribute('aria-hidden', 'true');
        if (approvalResolver) approvalResolver(payload || null);
        approvalResolver = null;
    };
    confirmCancel?.addEventListener('click', () => closeConfirm(false));
    confirmOk?.addEventListener('click', () => closeConfirm(true));
    confirmBackdrop?.addEventListener('click', (e) => {
        if (e.target === confirmBackdrop) closeConfirm(false);
    });
    approvalCancel?.addEventListener('click', () => closeApproval(null));
    approvalModal?.addEventListener('click', (e) => {
        if (e.target === approvalModal) closeApproval(null);
    });
    approvalSubmit?.addEventListener('click', () => {
        const reason = String(approvalReason?.value || '').trim();
        const email = String(approvalEmail?.value || '').trim();
        const password = String(approvalPassword?.value || '');
        if (reason.length < 5 || email === '' || password === '') {
            if (approvalError) {
                approvalError.textContent = 'Lengkapi alasan minimal 5 karakter, email, dan password manager.';
                approvalError.style.display = 'block';
            }
            return;
        }
        closeApproval({ reason, email, password });
    });

    const recalc = () => {
        let total = 0;
        qtyInputs.forEach((input) => {
            const unit = Number(input.dataset.unit || 0);
            const qty = Number(input.value || 0);
            total += (unit * qty);
            input.classList.remove('is-invalid-refund');
        });
        if (preview) preview.textContent = toMoney(total);
    };

    checks.forEach((check) => {
        check.addEventListener('change', () => {
            const target = document.getElementById(check.dataset.target);
            if (!target) return;
            target.disabled = !check.checked;
            if (!check.checked) target.value = 0;
            recalc();
        });
    });

    qtyInputs.forEach((input) => input.addEventListener('input', recalc));

    const copyBtn = document.getElementById('copyInvoiceBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', async () => {
            const invoice = String(copyBtn.dataset.invoice || '').trim();
            if (!invoice) return;
            try {
                await navigator.clipboard.writeText(invoice);
                copyBtn.textContent = 'Copied';
                pushToast('success', 'Invoice berhasil disalin.');
                setTimeout(() => { copyBtn.textContent = 'Copy'; }, 1200);
            } catch (e) {}
        });
    }
    const copySummaryBtn = document.getElementById('copySummaryBtn');
    if (copySummaryBtn) {
        copySummaryBtn.addEventListener('click', async () => {
            const summary = [
                `Invoice: {{ $sale->invoice_number }}`,
                `Status: {{ strtoupper($sale->status->value) }}`,
                `Metode: {{ $paymentMethodLabel }}`,
                @if(!empty($qrisRef))
                `Ref QRIS: {{ $qrisRef }}`,
                @endif
                @if(!empty($qrisIssuer))
                `Issuer QRIS: {{ $qrisIssuer }}`,
                @endif
                `Total: Rp {{ number_format($sale->total_amount, 0, ',', '.') }}`,
                `Dibayar: Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}`,
                `Kembalian: Rp {{ number_format($sale->change_amount, 0, ',', '.') }}`,
            ].join('\n');
            try {
                await navigator.clipboard.writeText(summary);
                pushToast('success', 'Ringkasan transaksi berhasil disalin.');
            } catch (e) {
                pushToast('error', 'Gagal menyalin ringkasan transaksi.');
            }
        });
    }

    const refundFilter = document.getElementById('refundItemFilter');
    if (refundFilter) {
        refundFilter.addEventListener('input', () => {
            const needle = String(refundFilter.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('.sales-refund-table tbody tr');
            rows.forEach((row) => {
                const nameEl = row.querySelector('.refund-item-name');
                if (!nameEl) return;
                const hay = String(nameEl.textContent || '').toLowerCase();
                row.style.display = !needle || hay.includes(needle) ? '' : 'none';
            });
        });
    }

    const partialForm = document.getElementById('partial-refund-form');
    if (partialForm) {
        partialForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            let checkedCount = 0;
            let validQtyCount = 0;
            checks.forEach((check) => {
                const target = document.getElementById(check.dataset.target);
                if (!target) return;
                target.classList.remove('is-invalid-refund');
                if (check.checked) {
                    checkedCount++;
                    if (Number(target.value || 0) > 0) {
                        validQtyCount++;
                    } else {
                        target.classList.add('is-invalid-refund');
                    }
                }
            });
            if (checkedCount === 0 || validQtyCount === 0) {
                pushToast('error', 'Pilih item dan isi qty refund lebih dari 0.');
                return;
            }
            const ok = await askConfirm('Simpan partial refund?');
            if (!ok) return;
            partialForm.submit();
        });
    }

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            if (form.id === 'partial-refund-form') return;
            const btn = e.submitter;
            const confirmMsg = btn?.dataset?.confirm;
            if (!confirmMsg) return;
            e.preventDefault();
            const ok = await askConfirm(confirmMsg);
            if (!ok) return;
            if (form.dataset.approval === 'manager') {
                approvalForm = form;
                const payload = await askApproval();
                if (!payload) return;
                const reasonField = form.querySelector('textarea[name="reason"]');
                if (reasonField && String(reasonField.value || '').trim().length < 5) {
                    reasonField.value = payload.reason;
                }
                let approvalReasonField = form.querySelector('input[name="manager_approval_reason"]');
                if (!approvalReasonField) {
                    approvalReasonField = document.createElement('input');
                    approvalReasonField.type = 'hidden';
                    approvalReasonField.name = 'manager_approval_reason';
                    form.appendChild(approvalReasonField);
                }
                approvalReasonField.value = payload.reason;
                let emailField = form.querySelector('input[name="manager_approval_email"]');
                if (!emailField) {
                    emailField = document.createElement('input');
                    emailField.type = 'hidden';
                    emailField.name = 'manager_approval_email';
                    form.appendChild(emailField);
                }
                emailField.value = payload.email;
                let passwordField = form.querySelector('input[name="manager_approval_password"]');
                if (!passwordField) {
                    passwordField = document.createElement('input');
                    passwordField.type = 'hidden';
                    passwordField.name = 'manager_approval_password';
                    form.appendChild(passwordField);
                }
                passwordField.value = payload.password;
            }
            form.submit();
        });
    });
});
</script>
